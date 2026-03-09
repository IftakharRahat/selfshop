<?php

namespace App\Http\Controllers;

use App\Models\Varient;
use App\Models\VariantSize;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Stock;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorProductController extends Controller
{
    private function getVendor()
    {
        $user = Auth::user();
        if (!$user || !$user->vendor) {
            return null;
        }
        return $user->vendor;
    }

    /** GET /api/vendor/products */
    public function index(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $query = Product::where('vendor_id', $vendor->id)->orderByDesc('created_at');

        if ($request->filled('search')) {
            $query->where('ProductName', 'like', '%' . $request->search . '%');
        }

        $products = $query->get(['id', 'ProductName', 'ProductSlug', 'ProductSku', 'qty', 'ProductResellerPrice', 'ProductRegularPrice', 'status', 'frature', 'ViewProductImage', 'vendor_approval_status', 'selling_type', 'created_at']);

        return response()->json([
            'status' => true,
            'data' => ['products' => $products],
        ]);
    }

    /** POST /api/vendor/products */
    public function store(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ProductName' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'brand_id' => 'required|exists:brands,id',
            'ProductImage' => 'nullable|image|max:5120',
            'ProductBreaf' => 'nullable|string',
            'ProductDetails' => 'nullable|string',
            'ProductResellerPrice' => 'nullable|numeric|min:0',
            'ProductRegularPrice' => 'nullable|numeric|min:0',
            'qty' => 'nullable|integer|min:0',
            'low_stock' => 'nullable|integer|min:0',
            'ProductSku' => 'nullable|string|max:100',
            'show_stock' => 'nullable|in:On,Off',
            'show_stock_text' => 'nullable|in:On,Off',
            'product_weight' => 'nullable|numeric|min:0',
            'minimum_qty' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'MetaKey' => 'nullable|string|max:500',
            'Discount' => 'nullable|numeric|min:0',
            'PostImage' => 'nullable|array',
            'PostImage.*' => 'image|max:5120',
            'allow_dropship' => 'nullable|boolean',
            'selling_type' => 'nullable|in:wholesale,dropshipping,both',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $catalogError = $this->validateCatalogSelection(
            (int) $data['category_id'],
            (int) $data['subcategory_id'],
            (int) $data['brand_id']
        );
        if ($catalogError) {
            return response()->json([
                'status' => false,
                'message' => $catalogError,
            ], 422);
        }

        $slug = Str::slug($data['ProductName']);
        $i = 1;
        while (Product::where('ProductSlug', $slug)->exists()) {
            $slug = Str::slug($data['ProductName']) . '-' . $i++;
        }

        $sku = $data['ProductSku'] ?? ('VP' . time() . rand(100, 999));

        $product = new Product();
        $product->vendor_id = $vendor->id;
        $product->category_id = $data['category_id'];
        $product->subcategory_id = $data['subcategory_id'];
        $product->brand_id = $data['brand_id'];
        if (Schema::hasColumn('products', 'minicategory_id')) {
            $product->minicategory_id = $request->input('minicategory_id');
        }
        $product->ProductName = $data['ProductName'];
        $product->ProductSlug = $slug;
        $product->ProductSku = $sku;
        $product->ProductBreaf = $data['ProductBreaf'] ?? null;
        $product->ProductDetails = $data['ProductDetails'] ?? null;
        $product->ProductResellerPrice = $data['ProductResellerPrice'] ?? 0;

        // Auto-calculate storefront price with category commission (this is what customers pay)
        $commissionService = app(\App\Services\VendorCommissionService::class);
        $displayPrice = $commissionService->getStorefrontPrice(
            (float) $product->ProductResellerPrice,
            (int) $vendor->id,
            (int) $product->category_id
        );

        // Regular Price is MSRP, can be set manually by vendor. 
        // If not provided, fallback to displayPrice for consistency.
        $product->ProductRegularPrice = $data['ProductRegularPrice'] ?? $displayPrice;
        $product->ProductSalePrice = $displayPrice;
        $product->ProductWholesalePrice = $product->ProductResellerPrice;
        $product->min_sell_price = $product->ProductResellerPrice;
        $product->qty = $data['qty'] ?? 0;
        $product->low_stock = $data['low_stock'] ?? 0;
        $product->show_stock = $data['show_stock'] ?? 'On';
        $product->show_stock_text = $data['show_stock_text'] ?? 'Off';
        $product->product_weight = $request->input('product_weight', 0);
        $product->minimum_qty = (int) $request->input('minimum_qty', 1);
        $product->weight = $request->input('unit');
        $product->MetaKey = $request->input('MetaKey');
        $product->Discount = $request->input('Discount', 0);
        $product->vendor_approval_status = 'pending';
        $product->status = 'Inactive';
        if (Schema::hasColumn('products', 'allow_dropship')) {
            $product->allow_dropship = (bool) $request->input('allow_dropship', false);
        }
        if (Schema::hasColumn('products', 'selling_type')) {
            $product->selling_type = $request->input('selling_type', 'both');
        }

        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');

        if ($request->hasFile('ProductImage')) {
            $img = $request->file('ProductImage');
            $safeName = Str::slug(pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $img->getClientOriginalExtension();
            $path = $img->storeAs('products/vendor', $safeName, 'r2');
            $product->ProductImage = $r2BaseUrl . '/' . $path;
            $product->ViewProductImage = $r2BaseUrl . '/' . $path;
        } else {
            $product->ProductImage = 'public/images/product/default.jpg';
            $product->ViewProductImage = 'public/images/product/default.jpg';
        }

        if ($request->hasFile('PostImage')) {
            $imageData = [];
            foreach ($request->file('PostImage') as $galleryImg) {
                $safeName = Str::slug(pathinfo($galleryImg->getClientOriginalName(), PATHINFO_FILENAME))
                    . '_' . Str::random(8) . '.' . $galleryImg->getClientOriginalExtension();
                $path = $galleryImg->storeAs('products/vendor/gallery', $safeName, 'r2');
                $imageData[] = $r2BaseUrl . '/' . $path;
            }
            $product->PostImage = json_encode($imageData);
        }

        $product->save();
        $this->refreshProductAggregation($product);

        Stock::create(['product_id' => $product->id, 'purchase' => 0, 'stock' => $product->qty]);
        Purchase::create([
            'date' => date('Y-m-d'),
            'invoiceID' => 'VENDOR-' . $product->id,
            'product_id' => $product->id,
            'supplier_id' => 1,
            'quantity' => $product->qty,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product created',
            'data' => ['product' => $product],
        ], 201);
    }

    /** GET /api/vendor/products/{id} */
    public function show($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        return response()->json(['status' => true, 'data' => ['product' => $product]]);
    }

    /** PUT /api/vendor/products/{id} */
    public function update(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'ProductName' => 'sometimes|string|max:255',
            'ProductBreaf' => 'nullable|string',
            'ProductDetails' => 'nullable|string',
            'ProductResellerPrice' => 'nullable|numeric|min:0',
            'ProductRegularPrice' => 'nullable|numeric|min:0',
            'qty' => 'nullable|integer|min:0',
            'low_stock' => 'nullable|integer|min:0',
            'ProductSku' => 'nullable|string|max:100',
            'show_stock' => 'nullable|in:On,Off',
            'show_stock_text' => 'nullable|in:On,Off',
            'status' => 'nullable|in:Active,Inactive',
            'ProductImage' => 'nullable|image|max:5120',
            'product_weight' => 'nullable|numeric|min:0',
            'minimum_qty' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'MetaKey' => 'nullable|string|max:500',
            'Discount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'sometimes|exists:subcategories,id',
            'brand_id' => 'sometimes|exists:brands,id',
            'PostImage' => 'nullable|array',
            'PostImage.*' => 'image|max:5120',
            'allow_dropship' => 'nullable|boolean',
            'selling_type' => 'nullable|in:wholesale,dropshipping,both',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $effectiveCategoryId = array_key_exists('category_id', $data)
            ? (int) $data['category_id']
            : (int) $product->category_id;
        $effectiveSubcategoryId = array_key_exists('subcategory_id', $data)
            ? (int) $data['subcategory_id']
            : (int) $product->subcategory_id;
        $effectiveBrandId = array_key_exists('brand_id', $data)
            ? (int) $data['brand_id']
            : (int) $product->brand_id;

        $catalogError = $this->validateCatalogSelection(
            $effectiveCategoryId,
            $effectiveSubcategoryId,
            $effectiveBrandId
        );
        if ($catalogError) {
            return response()->json([
                'status' => false,
                'message' => $catalogError,
            ], 422);
        }

        foreach (['ProductName', 'ProductBreaf', 'ProductDetails', 'ProductResellerPrice', 'ProductRegularPrice', 'qty', 'low_stock', 'ProductSku', 'show_stock', 'show_stock_text', 'status', 'MetaKey', 'Discount', 'category_id', 'subcategory_id', 'brand_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $product->{$key} = $data[$key];
            }
        }

        // Always recalculate storefront price (ProductSalePrice) when Reseller Price or category changes
        $commissionService = app(\App\Services\VendorCommissionService::class);
        $displayPrice = $commissionService->getStorefrontPrice(
            (float) $product->ProductResellerPrice,
            (int) $vendor->id,
            (int) $product->category_id
        );

        // If regular price was NOT manually updated in this request AND was exactly the old display price, 
        // we might want to update it. But per user request "it should not automatically update",
        // we will only update it if it's currently 0 or empty.
        if (empty($product->ProductRegularPrice) || $product->ProductRegularPrice == 0) {
            $product->ProductRegularPrice = $displayPrice;
        }

        $product->ProductSalePrice = $displayPrice;
        $product->ProductWholesalePrice = $product->ProductResellerPrice;
        $product->min_sell_price = $product->ProductResellerPrice;
        if (array_key_exists('unit', $data)) {
            $product->weight = $data['unit'];
        }
        if (array_key_exists('product_weight', $data)) {
            $product->product_weight = $data['product_weight'];
        }
        if (array_key_exists('minimum_qty', $data)) {
            $product->minimum_qty = (int) $data['minimum_qty'];
        }
        if (Schema::hasColumn('products', 'allow_dropship') && array_key_exists('allow_dropship', $data)) {
            $product->allow_dropship = (bool) $data['allow_dropship'];
        }
        if (Schema::hasColumn('products', 'selling_type') && array_key_exists('selling_type', $data)) {
            $product->selling_type = $data['selling_type'];
        }

        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');

        if ($request->hasFile('ProductImage')) {
            $img = $request->file('ProductImage');
            $safeName = Str::slug(pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $img->getClientOriginalExtension();
            $path = $img->storeAs('products/vendor', $safeName, 'r2');
            $product->ProductImage = $r2BaseUrl . '/' . $path;
            $product->ViewProductImage = $r2BaseUrl . '/' . $path;
        }
        if ($request->hasFile('PostImage')) {
            $imageData = [];
            foreach ($request->file('PostImage') as $galleryImg) {
                $safeName = Str::slug(pathinfo($galleryImg->getClientOriginalName(), PATHINFO_FILENAME))
                    . '_' . Str::random(8) . '.' . $galleryImg->getClientOriginalExtension();
                $path = $galleryImg->storeAs('products/vendor/gallery', $safeName, 'r2');
                $imageData[] = $r2BaseUrl . '/' . $path;
            }
            $product->PostImage = json_encode($imageData);
        }
        $product->save();
        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Product updated', 'data' => ['product' => $product]]);
    }

    /** PUT /api/vendor/products/{id}/status - toggle Published (Active/Inactive) */
    public function updateStatus(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $request->validate(['status' => 'required|in:Active,Inactive']);
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $product->status = $request->status;
        $product->save();
        return response()->json(['status' => true, 'message' => 'Status updated', 'data' => ['product' => $product]]);
    }

    /** PUT /api/vendor/products/{id}/stock-status - toggle In Stock/Out of Stock (0 or 1) */
    public function updateStockStatus(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $request->validate(['in_stock' => 'required|in:0,1']);
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $product->frature = (int) $request->in_stock;
        $product->save();
        return response()->json(['status' => true, 'message' => 'Stock status updated', 'data' => ['product' => $product]]);
    }

    /** DELETE /api/vendor/products/{id} */
    public function destroy($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $product->delete();
        return response()->json(['status' => true, 'message' => 'Product deleted']);
    }

    /** GET /api/vendor/products/{id}/variants */
    public function variants($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variants = Varient::with(['sizes.bulkPrices'])->where('product_id', $product->id)->orderBy('id')->get();
        return response()->json(['status' => true, 'data' => ['variants' => $variants]]);
    }

    /** POST /api/vendor/products/{id}/variants */
    public function storeVariant(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'qty' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'color_name' => 'nullable|string|max:100',
            'color_code' => ['nullable', 'string', 'regex:/^#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$/'],
            'image' => 'nullable|image|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        $variant = new Varient();
        $variant->product_id = $product->id;
        $variant->title = trim((string) ($data['title'] ?? $data['color_name'] ?? 'Variant'));
        $variant->qty = (int) $data['qty'];
        $variant->price = (float) $data['price'];
        $variant->color_name = $this->normalizeColorName($data['color_name'] ?? null);
        $variant->color_code = $this->normalizeColorCode($data['color_code'] ?? null);
        $variant->status = $request->input('status', 'Active');
        if ($request->hasFile('image')) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $img = $request->file('image');
            $safeName = Str::slug(pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $img->getClientOriginalExtension();
            $path = $img->storeAs('products/variants', $safeName, 'r2');
            $variant->image = $r2BaseUrl . '/' . $path;
        }
        $variant->save();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Variant added', 'data' => ['variant' => $variant]], 201);
    }

    /** PUT /api/vendor/products/{id}/variants/{variantId} */
    public function updateVariant(Request $request, $id, $variantId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'qty' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:Active,Inactive',
            'color_name' => 'nullable|string|max:100',
            'color_code' => ['nullable', 'string', 'regex:/^#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$/'],
            'image' => 'nullable|image|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        if (array_key_exists('title', $data)) {
            $variant->title = trim((string) ($data['title'] ?? $data['color_name'] ?? $variant->title));
        }
        if (array_key_exists('qty', $data)) $variant->qty = (int) $data['qty'];
        if (array_key_exists('price', $data)) $variant->price = (float) $data['price'];
        if (array_key_exists('status', $data)) $variant->status = $data['status'];
        if (array_key_exists('color_name', $data)) {
            $variant->color_name = $this->normalizeColorName($data['color_name']);
        }
        if (array_key_exists('color_code', $data)) {
            $variant->color_code = $this->normalizeColorCode($data['color_code']);
        }
        if ($request->hasFile('image')) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $img = $request->file('image');
            $safeName = Str::slug(pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $img->getClientOriginalExtension();
            $path = $img->storeAs('products/variants', $safeName, 'r2');
            $variant->image = $r2BaseUrl . '/' . $path;
        }
        $variant->save();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Variant updated', 'data' => ['variant' => $variant]]);
    }

    /** DELETE /api/vendor/products/{id}/variants/{variantId} */
    public function destroyVariant($id, $variantId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $variant->delete();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Variant deleted']);
    }

    /** GET /api/vendor/products/{id}/variants/{variantId}/sizes */
    public function sizes($id, $variantId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $sizes = VariantSize::where('varient_id', $variant->id)->orderBy('id')->get();
        return response()->json(['status' => true, 'data' => ['sizes' => $sizes]]);
    }

    /** POST /api/vendor/products/{id}/variants/{variantId}/sizes */
    public function storeSize(Request $request, $id, $variantId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $validator = Validator::make($request->all(), [
            'size_name' => 'required|string|max:50',
            'qty' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Active,Inactive',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        $size = new VariantSize();
        $size->varient_id = $variant->id;
        $size->size_name = trim($data['size_name']);
        $size->qty = (int) $data['qty'];
        $size->price = array_key_exists('price', $data) && $data['price'] !== null ? (float) $data['price'] : null;
        $size->status = $request->input('status', 'Active');
        $size->save();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Size added', 'data' => ['size' => $size]], 201);
    }

    /** PUT /api/vendor/products/{id}/variants/{variantId}/sizes/{sizeId} */
    public function updateSize(Request $request, $id, $variantId, $sizeId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $size = VariantSize::where('varient_id', $variant->id)->findOrFail($sizeId);
        $validator = Validator::make($request->all(), [
            'size_name' => 'sometimes|string|max:50',
            'qty' => 'sometimes|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:Active,Inactive',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        if (array_key_exists('size_name', $data)) $size->size_name = trim($data['size_name']);
        if (array_key_exists('qty', $data)) $size->qty = (int) $data['qty'];
        if (array_key_exists('price', $data)) {
            $size->price = $data['price'] !== null ? (float) $data['price'] : null;
        }
        if (array_key_exists('status', $data)) $size->status = $data['status'];
        $size->save();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Size updated', 'data' => ['size' => $size]]);
    }

    /** DELETE /api/vendor/products/{id}/variants/{variantId}/sizes/{sizeId} */
    public function destroySize($id, $variantId, $sizeId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $size = VariantSize::where('varient_id', $variant->id)->findOrFail($sizeId);
        $size->delete();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Size deleted']);
    }

    /** POST /api/vendor/products/{id}/variants/{variantId}/sizes/{sizeId}/bulk-prices */
    public function storeSizeBulkPrice(Request $request, $id, $variantId, $sizeId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $size = VariantSize::where('varient_id', $variant->id)->findOrFail($sizeId);

        $validator = Validator::make($request->all(), [
            'min_qty' => 'required|integer|min:1',
            'max_qty' => 'nullable|integer|min:1',
            'bulk_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $bulk = new \App\Models\VariantSizeBulkPrice();
        $bulk->variant_size_id = $size->id;
        $bulk->min_qty = (int)$request->min_qty;
        $bulk->max_qty = $request->max_qty ? (int)$request->max_qty : null;
        $bulk->bulk_price = (float)$request->bulk_price;
        $bulk->save();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Bulk price added', 'data' => ['bulk_price' => $bulk]], 201);
    }

    /** DELETE /api/vendor/products/{id}/variants/{variantId}/sizes/{sizeId}/bulk-prices/{bulkId} */
    public function destroySizeBulkPrice($id, $variantId, $sizeId, $bulkId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $size = VariantSize::where('varient_id', $variant->id)->findOrFail($sizeId);
        
        $bulk = \App\Models\VariantSizeBulkPrice::where('variant_size_id', $size->id)->findOrFail($bulkId);
        $bulk->delete();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Bulk price deleted']);
    }

    /** GET /api/vendor/products/bulk-template - CSV template download */
    public function bulkTemplate(): StreamedResponse
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            abort(403, 'Vendor not found');
        }
        $headers = [
            'ProductName', 'category_id', 'subcategory_id', 'brand_id', 'ProductBreaf', 'ProductDetails',
            'ProductResellerPrice', 'ProductRegularPrice', 'qty', 'low_stock', 'ProductSku', 'minimum_qty', 'unit', 'product_weight', 'MetaKey', 'Discount', 'allow_dropship',
        ];
        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, ['Example Product', '1', '1', '1', 'Short desc', 'Long desc', '100', '150', '50', '5', 'SKU001', '1', 'kg', '1', 'tag1,tag2', '0', '0']);
            fclose($out);
        }, 'vendor-products-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /** POST /api/vendor/products/bulk-upload - CSV bulk import */
    public function bulkUpload(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $created = 0;
        $errors = [];
        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) < 10) {
                $errors[] = ['row' => $rowNum, 'message' => 'Too few columns'];
                continue;
            }
            $data = array_combine($header ?: [], $row);
            if (!$data) {
                $errors[] = ['row' => $rowNum, 'message' => 'Invalid row'];
                continue;
            }
            $name = $data['ProductName'] ?? trim($row[0] ?? '');
            if (empty($name)) {
                $errors[] = ['row' => $rowNum, 'message' => 'ProductName required'];
                continue;
            }
            $validator = Validator::make([
                'ProductName' => $name,
                'category_id' => $data['category_id'] ?? $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'ProductResellerPrice' => $data['ProductResellerPrice'] ?? 0,
                'ProductRegularPrice' => $data['ProductRegularPrice'] ?? 0,
                'qty' => $data['qty'] ?? 0,
                'minimum_qty' => $data['minimum_qty'] ?? 1,
            ], [
                'ProductName' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'subcategory_id' => 'required|exists:subcategories,id',
                'brand_id' => 'required|exists:brands,id',
                'ProductResellerPrice' => 'nullable|numeric|min:0',
                'ProductRegularPrice' => 'nullable|numeric|min:0',
                'qty' => 'nullable|integer|min:0',
                'minimum_qty' => 'nullable|integer|min:1',
            ]);
            if ($validator->fails()) {
                $errors[] = ['row' => $rowNum, 'message' => $validator->errors()->first()];
                continue;
            }

            $categoryId = (int) ($data['category_id'] ?? 0);
            $subcategoryId = (int) ($data['subcategory_id'] ?? 0);
            $brandId = (int) ($data['brand_id'] ?? 0);
            $catalogError = $this->validateCatalogSelection($categoryId, $subcategoryId, $brandId);
            if ($catalogError) {
                $errors[] = ['row' => $rowNum, 'message' => $catalogError];
                continue;
            }

            $slug = Str::slug($name);
            $i = 1;
            while (Product::where('ProductSlug', $slug)->exists()) {
                $slug = Str::slug($name) . '-' . $i++;
            }
            $product = new Product();
            $product->vendor_id = $vendor->id;
            $product->category_id = $categoryId;
            $product->subcategory_id = $subcategoryId;
            $product->brand_id = $brandId;
            $product->ProductName = $name;
            $product->ProductSlug = $slug;
            $product->ProductSku = !empty($data['ProductSku']) ? $data['ProductSku'] : ('VP' . time() . rand(100, 999) . $rowNum);
            $product->ProductBreaf = $data['ProductBreaf'] ?? null;
            $product->ProductDetails = $data['ProductDetails'] ?? null;
            $product->ProductResellerPrice = (float) ($data['ProductResellerPrice'] ?? 0);
            
            // Auto-calculate storefront price with category commission
            $commissionService = app(\App\Services\VendorCommissionService::class);
            $displayPrice = $commissionService->getStorefrontPrice(
                (float) $product->ProductResellerPrice,
                (int) $vendor->id,
                (int) $product->category_id
            );

            $product->ProductRegularPrice = $displayPrice;
            $product->ProductSalePrice = $displayPrice;
            $product->ProductWholesalePrice = $product->ProductResellerPrice;
            $product->min_sell_price = $product->ProductResellerPrice;
            $product->qty = (int) ($data['qty'] ?? 0);
            $product->low_stock = (int) ($data['low_stock'] ?? 0);
            $product->minimum_qty = (int) ($data['minimum_qty'] ?? 1);
            $product->product_weight = (float) ($data['product_weight'] ?? 0);
            $product->weight = $data['unit'] ?? null;
            $product->MetaKey = $data['MetaKey'] ?? null;
            $product->Discount = $data['Discount'] ?? 0;
            $product->vendor_approval_status = 'pending';
            $product->status = 'Inactive';
            $product->ProductImage = 'public/images/product/default.jpg';
            $product->ViewProductImage = 'public/images/product/default.jpg';
            if (Schema::hasColumn('products', 'allow_dropship')) {
                $product->allow_dropship = in_array(strtolower($data['allow_dropship'] ?? '0'), ['1', 'true', 'yes'], true);
            }
            $product->save();
            Stock::create(['product_id' => $product->id, 'purchase' => 0, 'stock' => $product->qty]);
            Purchase::create([
                'date' => date('Y-m-d'),
                'invoiceID' => 'VENDOR-' . $product->id,
                'product_id' => $product->id,
                'supplier_id' => 1,
                'quantity' => $product->qty,
            ]);
            $created++;
        }
        fclose($handle);
        return response()->json([
            'status' => true,
            'message' => "Created {$created} product(s)" . (count($errors) ? ', ' . count($errors) . ' row(s) had errors.' : ''),
            'data' => ['created' => $created, 'errors' => $errors],
        ]);
    }

    /**
     * Vendors must use existing active catalog records from the main website.
     * Returns null when selection is valid; otherwise an error message.
     */
    private function validateCatalogSelection(int $categoryId, int $subcategoryId, int $brandId): ?string
    {
        if ($categoryId <= 0 || !Category::where('id', $categoryId)->where('status', 'Active')->exists()) {
            return 'Selected category is invalid or inactive. Use an active category from main website.';
        }

        if ($subcategoryId <= 0 || !Subcategory::where('id', $subcategoryId)->where('category_id', $categoryId)->where('status', 'Active')->exists()) {
            return 'Selected subcategory is invalid for the chosen category.';
        }

        if ($brandId <= 0 || !Brand::where('id', $brandId)->where('status', 'Active')->exists()) {
            return 'Selected brand is invalid or inactive.';
        }

        return null;
    }

    private function normalizeColorCode(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $clean = strtoupper(ltrim(trim($value), '#'));
        if ($clean === '') {
            return null;
        }

        return '#' . $clean;
    }

    private function normalizeColorName(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $clean = trim($value);
        return $clean === '' ? null : $clean;
    }

    /** GET /api/vendor/products/{id}/price-tiers */
    public function priceTiers($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $tiers = ProductPriceTier::where('product_id', $product->id)->orderBy('min_qty')->get();
        return response()->json(['status' => true, 'data' => ['price_tiers' => $tiers]]);
    }

    /** POST /api/vendor/products/{id}/price-tiers */
    public function storePriceTier(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $validator = Validator::make($request->all(), [
            'min_qty' => 'required|integer|min:0',
            'max_qty' => 'nullable|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'tier_label' => 'nullable|string|max:50',
            'variant_title' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $tier = new ProductPriceTier();
        $tier->product_id = $product->id;
        $tier->min_qty = (int) $request->min_qty;
        $tier->max_qty = $request->filled('max_qty') ? (int) $request->max_qty : null;
        $tier->unit_price = $request->unit_price;
        $tier->delivery_charge = $request->filled('delivery_charge') ? $request->delivery_charge : null;
        $tier->tier_label = $request->input('tier_label', 'Tier');
        $tier->variant_title = $request->input('variant_title');
        $tier->save();
        return response()->json(['status' => true, 'message' => 'Price tier added', 'data' => ['price_tier' => $tier]], 201);
    }

    /** PUT /api/vendor/products/{id}/price-tiers/{tierId} */
    public function updatePriceTier(Request $request, $id, $tierId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $tier = ProductPriceTier::where('product_id', $product->id)->findOrFail($tierId);
        $validator = Validator::make($request->all(), [
            'min_qty' => 'sometimes|integer|min:0',
            'max_qty' => 'nullable|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'tier_label' => 'sometimes|string|max:50',
            'variant_title' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        if ($request->has('min_qty')) $tier->min_qty = (int) $request->min_qty;
        if ($request->has('max_qty')) $tier->max_qty = $request->filled('max_qty') ? (int) $request->max_qty : null;
        if ($request->has('unit_price')) $tier->unit_price = $request->unit_price;
        if ($request->has('delivery_charge')) $tier->delivery_charge = $request->filled('delivery_charge') ? $request->delivery_charge : null;
        if ($request->has('tier_label')) $tier->tier_label = $request->tier_label;
        if ($request->has('variant_title')) $tier->variant_title = $request->variant_title;
        $tier->save();
        return response()->json(['status' => true, 'message' => 'Price tier updated', 'data' => ['price_tier' => $tier]]);
    }

    /** DELETE /api/vendor/products/{id}/price-tiers/{tierId} */
    public function destroyPriceTier($id, $tierId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $tier = ProductPriceTier::where('product_id', $product->id)->findOrFail($tierId);
        $tier->delete();
        return response()->json(['status' => true, 'message' => 'Price tier deleted']);
    }

    /** POST /api/vendor/products/{id}/refresh-aggregation */
    public function refreshAggregation($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        
        $this->refreshProductAggregation($product);
        
        return response()->json([
            'status' => true, 
            'message' => 'Product aggregation refreshed successfully',
            'data' => [
                'qty' => $product->qty,
                'price' => $product->ProductResellerPrice
            ]
        ]);
    }

    private function refreshProductAggregation(Product $product)
    {
        $variants = $product->varients()->with(['sizes.bulkPrices'])->get();
        if ($variants->isEmpty()) {
            return;
        }

        $totalQty = 0;
        $minPrice = null;
        $firstSizePrice = null;

        foreach ($variants as $index => $variant) {
            // Determine first size price for the default card display
            if ($index === 0) {
                if ($variant->sizes->isEmpty()) {
                    $vPrice = (float) ($variant->price ?: 0);
                    if ($vPrice > 0) {
                        $firstSizePrice = $vPrice;
                    }
                } else {
                    $firstSize = $variant->sizes->first();
                    if ($firstSize) {
                        $sPrice = (float) ($firstSize->price ?: 0);
                        $bP = 0;
                        if ($firstSize->bulkPrices && $firstSize->bulkPrices->isNotEmpty()) {
                            $bP = (float) ($firstSize->bulkPrices->first()->bulk_price ?: 0);
                        }
                        if ($sPrice > 0) {
                            $firstSizePrice = $sPrice;
                        } elseif ($bP > 0) {
                            $firstSizePrice = $bP;
                        }
                    }
                }
            }

            if ($variant->sizes->isEmpty()) {
                $totalQty += (int) $variant->qty;
                $vPrice = (float) ($variant->price ?: 0);
                if ($vPrice > 0 && ($minPrice === null || $vPrice < $minPrice)) {
                    $minPrice = $vPrice;
                }
            } else {
                foreach ($variant->sizes as $size) {
                    $totalQty += (int) $size->qty;

                    // Get the best price for this size (base or bulk)
                    $pricesToCompare = [];
                    $sPrice = (float) ($size->price ?: 0);
                    if ($sPrice > 0) {
                        $pricesToCompare[] = $sPrice;
                    }

                    // Check bulk prices as well
                    if ($size->bulkPrices && $size->bulkPrices->isNotEmpty()) {
                        foreach ($size->bulkPrices as $bp) {
                            $bpPrice = (float) ($bp->bulk_price ?: 0);
                            if ($bpPrice > 0) {
                                $pricesToCompare[] = $bpPrice;
                            }
                        }
                    }

                    if (!empty($pricesToCompare)) {
                        $bestSizePrice = min($pricesToCompare);
                        if ($minPrice === null || $bestSizePrice < $minPrice) {
                            $minPrice = $bestSizePrice;
                        }
                    }
                }
            }
        }

        $product->qty = $totalQty;
        if ($minPrice !== null && $minPrice > 0) {
            $product->ProductResellerPrice = $minPrice;
        }
        
        // Apply commission markup for storefront price (ProductRegularPrice)
        $commissionService = app(\App\Services\VendorCommissionService::class);
        $rawCardPrice = $firstSizePrice ?? $minPrice ?? $product->ProductResellerPrice;
        if ($rawCardPrice > 0) {
            $product->ProductRegularPrice = $commissionService->getStorefrontPrice(
                (float) $rawCardPrice,
                (int) $product->vendor_id,
                (int) $product->category_id
            );
        }

        $product->save();
    }
}
