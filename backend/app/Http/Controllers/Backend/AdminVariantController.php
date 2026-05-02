<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Varient;
use App\Models\VariantSize;
use App\Models\VariantSizeBulkPrice;
use App\Helpers\StorageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminVariantController extends Controller
{
    /**
     * GET /admin/products/{id}/variants-json
     * List all variants with nested sizes and bulk prices for a product.
     */
    public function index($id)
    {
        $product = Product::findOrFail($id);
        $variants = Varient::with(['sizes.bulkPrices'])
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->get();

        return response()->json(['status' => true, 'data' => ['variants' => $variants]]);
    }

    /**
     * POST /admin/products/{id}/variants-json
     * Create a new color variant for a product.
     */
    public function store(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'      => 'nullable|string|max:255',
            'qty'        => 'required|integer|min:0',
            'price'      => 'required|numeric|min:0',
            'color_name' => 'nullable|string|max:100',
            'color_code' => ['nullable', 'string', 'regex:/^#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$/'],
            'image'      => 'nullable|image|max:5120',
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
        $variant->color_name = $data['color_name'] ?? null;
        $variant->color_code = $data['color_code'] ?? null;
        $variant->status = $request->input('status', 'Active');

        if ($request->hasFile('image')) {
            $variant->image = StorageHelper::store($request->file('image'), 'admin/products/variants');
        }

        $variant->save();
        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Variant added', 'data' => ['variant' => $variant]], 201);
    }

    /**
     * DELETE /admin/products/{id}/variants-json/{variantId}
     * Remove a variant and all its sizes/bulk prices.
     */
    public function destroy($id, $variantId)
    {
        $product = Product::findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);

        // Delete nested bulk prices and sizes
        foreach ($variant->sizes as $size) {
            $size->bulkPrices()->delete();
        }
        $variant->sizes()->delete();
        $variant->delete();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Variant deleted']);
    }

    /**
     * POST /admin/products/{id}/variants-json/{variantId}/sizes
     * Add a size to a variant.
     */
    public function storeSize(Request $request, $id, $variantId)
    {
        $product = Product::findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);

        $validator = Validator::make($request->all(), [
            'size_name' => 'required|string|max:50',
            'qty'       => 'required|integer|min:0',
            'price'     => 'nullable|numeric|min:0',
            'status'    => 'nullable|in:Active,Inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $size = new VariantSize();
        $size->varient_id = $variant->id;
        $size->size_name = trim($data['size_name']);
        $size->qty = (int) $data['qty'];
        $size->price = isset($data['price']) && $data['price'] !== null ? (float) $data['price'] : null;
        $size->status = $request->input('status', 'Active');
        $size->save();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Size added', 'data' => ['size' => $size]], 201);
    }

    /**
     * DELETE /admin/products/{id}/variants-json/{variantId}/sizes/{sizeId}
     * Remove a size from a variant.
     */
    public function destroySize($id, $variantId, $sizeId)
    {
        $product = Product::findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $size = VariantSize::where('varient_id', $variant->id)->findOrFail($sizeId);

        $size->bulkPrices()->delete();
        $size->delete();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Size deleted']);
    }

    /**
     * POST /admin/products/{id}/variants-json/{variantId}/sizes/{sizeId}/bulk-prices
     * Add a bulk pricing tier to a size.
     */
    public function storeBulkPrice(Request $request, $id, $variantId, $sizeId)
    {
        $product = Product::findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $size = VariantSize::where('varient_id', $variant->id)->findOrFail($sizeId);

        $validator = Validator::make($request->all(), [
            'min_qty'    => 'required|integer|min:1',
            'max_qty'    => 'nullable|integer|min:1',
            'bulk_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $bulk = new VariantSizeBulkPrice();
        $bulk->variant_size_id = $size->id;
        $bulk->min_qty = (int) $request->min_qty;
        $bulk->max_qty = $request->max_qty ? (int) $request->max_qty : null;
        $bulk->bulk_price = (float) $request->bulk_price;
        $bulk->save();

        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Bulk price added', 'data' => ['bulk_price' => $bulk]], 201);
    }

    /**
     * DELETE /admin/products/{id}/variants-json/{variantId}/sizes/{sizeId}/bulk-prices/{bulkId}
     * Remove a bulk pricing tier.
     */
    public function destroyBulkPrice($id, $variantId, $sizeId, $bulkId)
    {
        $product = Product::findOrFail($id);
        $variant = Varient::where('product_id', $product->id)->findOrFail($variantId);
        $size = VariantSize::where('varient_id', $variant->id)->findOrFail($sizeId);
        $bulk = VariantSizeBulkPrice::where('variant_size_id', $size->id)->findOrFail($bulkId);

        $bulk->delete();
        $this->refreshProductAggregation($product);

        return response()->json(['status' => true, 'message' => 'Bulk price deleted']);
    }

    /**
     * POST /admin/products/ajax-store
     * Create product via AJAX (returns JSON with product ID so variants can be added).
     */
    public function ajaxStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ProductName'     => 'required|string|max:255',
            'category_id'     => 'required|exists:categories,id',
            'subcategory_id'  => 'nullable|exists:subcategories,id',
            'brand_id'        => 'nullable|exists:brands,id',
            'selling_type'    => 'nullable|in:wholesale,dropshipping,both',
            'ProductImage'    => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $product = new Product();
        $product->category_id = $request->category_id;
        $product->subcategory_id = $request->subcategory_id;
        $product->minicategory_id = $request->minicategory_id;
        $product->brand_id = $request->brand_id;
        $product->ProductName = $request->ProductName;
        $product->product_weight = $request->input('product_weight', 0);
        $product->minimum_qty = $request->input('minimum_qty', 1);
        $product->weight = $request->input('unit');
        $product->status = 'Active';

        // Set shop_id (same logic as ProductController@store)
        if ($request->has('shop_id')) {
            $product->shop_id = $request->shop_id;
        } else {
            $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
            if ($admin->type == 'Shop') {
                $product->shop_id = $admin->id;
            } else {
                $shop = \App\Models\Admin::where('id', $admin->add_by)->first();
                $product->shop_id = $shop ? $shop->id : $admin->id;
            }
        }

        // Generate slug
        $slug = \Illuminate\Support\Str::slug($request->ProductName);
        $i = 1;
        while (Product::where('ProductSlug', $slug)->exists()) {
            $slug = \Illuminate\Support\Str::slug($request->ProductName) . '-' . $i++;
        }
        $product->ProductSlug = $slug;
        $product->ProductSku = 'SP#000' . ((Product::latest('id')->first()->id ?? 0) + 1);

        // Images
        if ($request->hasFile('ProductImage')) {
            $fullUrl = StorageHelper::store($request->file('ProductImage'), 'admin/products');
            $product->ProductImage = $fullUrl;
            $product->ViewProductImage = $fullUrl;
        }
        if ($request->hasFile('PostImage')) {
            $imageData = [];
            foreach ($request->file('PostImage') as $imgfile) {
                $imageData[] = StorageHelper::store($imgfile, 'admin/products/gallery');
            }
            $product->PostImage = json_encode($imageData);
        }

        // Prices (force numeric - wholesale hides the price section, so fields may be empty)
        $product->ProductResellerPrice = floatval($request->input('ProductResellerPrice', 0));
        $product->ProductRegularPrice = floatval($request->input('ProductRegularPrice', 0));
        $product->ProductSalePrice = floatval($request->input('ProductSalePrice', 0));
        $product->ProductWholesalePrice = floatval($request->input('ProductWholesalePrice', 0));
        $product->min_sell_price = floatval($request->input('min_sell_price', 0));
        $product->Discount = floatval($request->input('Discount', 0));
        $product->qty = intval($request->input('qty', 0));
        $product->low_stock = intval($request->input('low_stock', 0));

        // Stock visibility
        $stockVis = $request->input('stock_visibility', 'quantity');
        $product->show_stock = $stockVis === 'quantity' ? 'On' : 'Off';
        $product->show_stock_text = $stockVis === 'text' ? 'On' : 'Off';

        // Description
        $product->ProductDetails = $request->input('ProductDetails');
        $product->ProductBreaf = $request->input('ProductBreaf');

        // SEO
        $product->MetaTitle = $request->input('MetaTitle');
        $product->MetaKey = $request->input('MetaKey');
        $product->MetaDescription = $request->input('MetaDescription');

        // YouTube
        $product->youtube_link = $request->input('youtube_link');

        // Selling type
        $product->selling_type = $request->input('selling_type', 'both');

        // Admin-only toggles
        $product->mart_status = $request->has('mart_status') ? 'On' : 'Off';
        $product->reseller_status = $request->has('reseller_status') ? 'On' : 'Off';
        $product->show_new_product = $request->has('show_new_product') ? 'On' : 'Off';
        $product->hot_list = $request->has('hot_list') ? 'On' : 'Off';
        $product->ready_bost = $request->has('ready_bost') ? 'On' : 'Off';
        $product->profitable = $request->has('profitable') ? 'On' : 'Off';
        $product->limited = $request->has('limited') ? 'On' : 'Off';
        $product->summer = $request->has('summer') ? 'On' : 'Off';
        $product->reseller_bonus = $request->input('reseller_bonus', 0);
        $product->ex_pack = $request->input('ex_pack', 0);
        $product->ex_dvc = $request->input('ex_dvc', 0);
        $product->shipping_days = $request->input('shipping_days');

        $product->save();

        // Create stock & purchase records
        \App\Models\Stock::create(['product_id' => $product->id, 'purchase' => 0, 'stock' => $product->qty]);
        \App\Models\Purchase::create([
            'date' => date('Y-m-d'),
            'invoiceID' => date('Y-m-d'),
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

    /**
     * Refresh product-level aggregation from variant data.
     */
    private function refreshProductAggregation(Product $product)
    {
        $product->load('varients.sizes');
        $variants = $product->varients ?? collect();

        if ($variants->isEmpty()) {
            return;
        }

        $totalQty = 0;
        $minPrice = PHP_FLOAT_MAX;

        foreach ($variants as $variant) {
            if ($variant->sizes && $variant->sizes->count() > 0) {
                foreach ($variant->sizes as $size) {
                    $totalQty += (int) $size->qty;
                    $effectivePrice = $size->price !== null ? (float) $size->price : (float) $variant->price;
                    if ($effectivePrice > 0 && $effectivePrice < $minPrice) {
                        $minPrice = $effectivePrice;
                    }
                }
            } else {
                $totalQty += (int) $variant->qty;
                if ($variant->price > 0 && $variant->price < $minPrice) {
                    $minPrice = (float) $variant->price;
                }
            }
        }

        if ($minPrice < PHP_FLOAT_MAX && $minPrice > 0) {
            $product->ProductResellerPrice = $minPrice;
            $product->ProductWholesalePrice = $minPrice;
            $product->min_sell_price = $minPrice;
            // Also set Regular and Sale price so storefront/search shows correct price
            if (floatval($product->ProductRegularPrice) <= 0) {
                $product->ProductRegularPrice = $minPrice;
            }
            if (floatval($product->ProductSalePrice) <= 0) {
                $product->ProductSalePrice = $minPrice;
            }
        }

        $product->qty = $totalQty;
        $product->save();
    }
}
