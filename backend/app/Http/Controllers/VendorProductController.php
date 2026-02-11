<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Varient;
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

        $products = $query->get(['id', 'ProductName', 'ProductSlug', 'ProductSku', 'qty', 'ProductResellerPrice', 'ProductRegularPrice', 'status', 'frature', 'ViewProductImage', 'vendor_approval_status', 'created_at']);

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
            'ProductImage' => 'nullable|image|max:2048',
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
            'PostImage.*' => 'image|max:2048',
            'allow_dropship' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

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
        $product->minicategory_id = $request->input('minicategory_id');
        $product->ProductName = $data['ProductName'];
        $product->ProductSlug = $slug;
        $product->ProductSku = $sku;
        $product->ProductBreaf = $data['ProductBreaf'] ?? null;
        $product->ProductDetails = $data['ProductDetails'] ?? null;
        $product->ProductResellerPrice = $data['ProductResellerPrice'] ?? 0;
        $product->ProductRegularPrice = $data['ProductRegularPrice'] ?? 0;
        $product->ProductWholesalePrice = $data['ProductResellerPrice'] ?? 0;
        $product->ProductSalePrice = $data['ProductRegularPrice'] ?? 0;
        $product->min_sell_price = $data['ProductResellerPrice'] ?? 0;
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

        if ($request->hasFile('ProductImage')) {
            $path = $request->file('ProductImage')->store('products/vendor', 'public');
            $product->ProductImage = 'storage/' . $path;
            $product->ViewProductImage = 'storage/' . $path;
        } else {
            $product->ProductImage = 'public/images/product/default.jpg';
            $product->ViewProductImage = 'public/images/product/default.jpg';
        }

        if ($request->hasFile('PostImage')) {
            $imageData = [];
            foreach ($request->file('PostImage') as $img) {
                $path = $img->store('products/vendor/gallery', 'public');
                $imageData[] = 'storage/' . $path;
            }
            $product->PostImage = json_encode($imageData);
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
            'ProductImage' => 'nullable|image|max:2048',
            'product_weight' => 'nullable|numeric|min:0',
            'minimum_qty' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'MetaKey' => 'nullable|string|max:500',
            'Discount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'sometimes|exists:subcategories,id',
            'brand_id' => 'sometimes|exists:brands,id',
            'PostImage' => 'nullable|array',
            'PostImage.*' => 'image|max:2048',
            'allow_dropship' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        foreach (['ProductName', 'ProductBreaf', 'ProductDetails', 'ProductResellerPrice', 'ProductRegularPrice', 'qty', 'low_stock', 'ProductSku', 'show_stock', 'show_stock_text', 'status', 'MetaKey', 'Discount', 'category_id', 'subcategory_id', 'brand_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $product->{$key} = $data[$key];
            }
        }
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

        if ($request->hasFile('ProductImage')) {
            $path = $request->file('ProductImage')->store('products/vendor', 'public');
            $product->ProductImage = 'storage/' . $path;
            $product->ViewProductImage = 'storage/' . $path;
        }
        if ($request->hasFile('PostImage')) {
            $imageData = [];
            foreach ($request->file('PostImage') as $img) {
                $path = $img->store('products/vendor/gallery', 'public');
                $imageData[] = 'storage/' . $path;
            }
            $product->PostImage = json_encode($imageData);
        }
        $product->save();

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

    /** PUT /api/vendor/products/{id}/featured - toggle Featured (0 or 1) */
    public function updateFeatured(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }
        $request->validate(['featured' => 'required|in:0,1']);
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $product->frature = (int) $request->featured;
        $product->save();
        return response()->json(['status' => true, 'message' => 'Featured updated', 'data' => ['product' => $product]]);
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
        $variants = Varient::where('product_id', $product->id)->orderBy('id')->get();
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
            'title' => 'required|string|max:255',
            'qty' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $variant = new Varient();
        $variant->product_id = $product->id;
        $variant->title = $request->title;
        $variant->qty = (int) $request->qty;
        $variant->price = (int) round($request->price);
        $variant->status = $request->input('status', 'Active');
        $variant->save();
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
            'title' => 'sometimes|string|max:255',
            'qty' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:Active,Inactive',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        if ($request->has('title')) $variant->title = $request->title;
        if ($request->has('qty')) $variant->qty = (int) $request->qty;
        if ($request->has('price')) $variant->price = (int) round($request->price);
        if ($request->has('status')) $variant->status = $request->status;
        $variant->save();
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
        return response()->json(['status' => true, 'message' => 'Variant deleted']);
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
            $slug = Str::slug($name);
            $i = 1;
            while (Product::where('ProductSlug', $slug)->exists()) {
                $slug = Str::slug($name) . '-' . $i++;
            }
            $product = new Product();
            $product->vendor_id = $vendor->id;
            $product->category_id = (int) ($data['category_id'] ?? 0);
            $product->subcategory_id = (int) ($data['subcategory_id'] ?? 0);
            $product->brand_id = (int) ($data['brand_id'] ?? 0);
            $product->ProductName = $name;
            $product->ProductSlug = $slug;
            $product->ProductSku = !empty($data['ProductSku']) ? $data['ProductSku'] : ('VP' . time() . rand(100, 999) . $rowNum);
            $product->ProductBreaf = $data['ProductBreaf'] ?? null;
            $product->ProductDetails = $data['ProductDetails'] ?? null;
            $product->ProductResellerPrice = (float) ($data['ProductResellerPrice'] ?? 0);
            $product->ProductRegularPrice = (float) ($data['ProductRegularPrice'] ?? 0);
            $product->ProductWholesalePrice = $product->ProductResellerPrice;
            $product->ProductSalePrice = $product->ProductRegularPrice;
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
            'unit_price' => 'required|numeric|min:0',
            'tier_label' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $tier = new ProductPriceTier();
        $tier->product_id = $product->id;
        $tier->min_qty = (int) $request->min_qty;
        $tier->unit_price = $request->unit_price;
        $tier->tier_label = $request->input('tier_label', 'Tier');
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
            'unit_price' => 'sometimes|numeric|min:0',
            'tier_label' => 'sometimes|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        if ($request->has('min_qty')) $tier->min_qty = (int) $request->min_qty;
        if ($request->has('unit_price')) $tier->unit_price = $request->unit_price;
        if ($request->has('tier_label')) $tier->tier_label = $request->tier_label;
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
}
