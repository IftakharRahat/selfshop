<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\Category;
use App\Models\Attrvalue;
use App\Models\Attribute;
use App\Models\Subcategory;
use App\Models\Stock;
use App\Models\Purchase;
use App\Models\Brand;
use App\Models\ProductPriceTier;
use App\Models\Varient;
use App\Helpers\StorageHelper;
use App\Services\VendorAdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DataTables;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct(
        protected VendorAdminNotificationService $vendorNotificationService
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sizes = Attrvalue::where('attribute_id', 2)->where('status', 'Active')->get();
        $colors = Attrvalue::where('attribute_id', 3)->where('status', 'Active')->get();
        $weights = Attrvalue::where('attribute_id', 1)->where('status', 'Active')->get();
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'status')->get();
        $subcategories = Subcategory::where('status', 'Active')->select('id', 'sub_category_name')->get();
        return view('backend.content.product.index', ['weights' => $weights, 'colors' => $colors, 'sizes' => $sizes, 'categories' => $categories, 'subcategories' => $subcategories]);
    }

    public function varients($id)
    {
        $product = Product::findOrfail($id);
        $varients = Varient::where('product_id', $id)->get();
        return view('backend.content.product.varient', ['varients' => $varients, 'product' => $product]);
    }

    public function shopindex()
    {
        $sizes = Attrvalue::where('attribute_id', 2)->where('status', 'Active')->get();
        $colors = Attrvalue::where('attribute_id', 3)->where('status', 'Active')->get();
        $weights = Attrvalue::where('attribute_id', 1)->where('status', 'Active')->get();
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'status')->get();
        $subcategories = Subcategory::where('status', 'Active')->select('id', 'sub_category_name')->get();
        return view('backend.content.product.shopindex', ['weights' => $weights, 'colors' => $colors, 'sizes' => $sizes, 'categories' => $categories, 'subcategories' => $subcategories]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function statusupdate(Request $request)
    {
        $product = Product::where('id', $request->product_id)->first();
        $product->status = $request->status;
        $product->update();
        return response()->json($product, 200);
    }

    public function featurestatusupdate(Request $request)
    {
        $product = Product::where('id', $request->product_id)->first();
        $product->frature = $request->frature;
        $product->update();
        return response()->json($product, 200);
    }

    public function bestsellstatusupdate(Request $request)
    {
        $product = Product::where('id', $request->product_id)->first();
        $product->best_selling = $request->best;
        $product->update();
        return response()->json($product, 200);
    }

    public function ratedstatusupdate(Request $request)
    {
        $product = Product::where('id', $request->product_id)->first();
        $product->top_rated = $request->top_rated;
        $product->update();
        return response()->json($product, 200);
    }

    public function create()
    {
        $shop = 'No';
        $sizes = Attrvalue::where('attribute_id', 2)->where('status', 'Active')->get();
        $colors = Attrvalue::where('attribute_id', 3)->where('status', 'Active')->get();
        $weights = Attrvalue::where('attribute_id', 1)->where('status', 'Active')->get();
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'status')->get();
        $brands = Brand::where('status', 'Active')->select('id', 'brand_name')->get();
        return view('backend.content.product.create', ['shop' => $shop, 'weights' => $weights, 'colors' => $colors, 'sizes' => $sizes, 'categories' => $categories, 'brands' => $brands]);
    }

    public function createproduct()
    {
        $shop = 'Yes';
        $sizes = Attrvalue::where('attribute_id', 2)->where('status', 'Active')->get();
        $colors = Attrvalue::where('attribute_id', 3)->where('status', 'Active')->get();
        $weights = Attrvalue::where('attribute_id', 1)->where('status', 'Active')->get();
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'status')->get();
        $brands = Brand::where('status', 'Active')->select('id', 'brand_name')->get();
        return view('backend.content.product.create', ['shop' => $shop, 'weights' => $weights, 'colors' => $colors, 'sizes' => $sizes, 'categories' => $categories, 'brands' => $brands]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product = new Product();

        $product->category_id = $request->category_id;
        $product->subcategory_id = $request->subcategory_id;
        $product->minicategory_id = $request->minicategory_id;
        $product->brand_id = $request->brand_id;
        if (isset($request->shop_id)) {
            $product->shop_id = $request->shop_id;
        } else {
            if (Auth::guard('admin')->user()->type == 'Shop') {
                $product->shop_id = Auth::guard('admin')->user()->id;
            } else {
                $shop = Admin::where('id', Auth::guard('admin')->user()->add_by)->first();
                $product->shop_id = $shop->id;
            }
        }

        $product->ProductName = $request->ProductName;
        $product->product_weight = $request->product_weight;
        $product->minimum_qty = $request->minimum_qty;

        $time = microtime('.') * 10000;

        $productImg = $request->file('ProductImage');
        if ($productImg) {
            $fullUrl = StorageHelper::store($productImg, 'admin/products');
            $product->ProductImage = $fullUrl;
            $product->ViewProductImage = $fullUrl;
        }
        $product->youtube_link = $request->youtube_link;
        if ($request->hasFile('PostImage')) {
            foreach ($request->file('PostImage') as $imgfiles) {
                $imageData[] = StorageHelper::store($imgfiles, 'admin/products/gallery');
            }
            $product->PostImage = json_encode($imageData);
        };

        if ($request->color) {
            $product->color = json_encode($request->color);
        }
        if ($request->size) {
            $product->size = json_encode($request->size);
        }
        if ($request->weight) {
            $product->weight = json_encode($request->weight);
        }

        $product->ProductBreaf = $request->ProductBreaf;
        $product->ProductDetails = $request->ProductDetails;

        $product->MetaTitle = $request->MetaTitle;
        $product->MetaKey = $request->MetaKey;
        $product->MetaDescription = $request->MetaDescription;
        $meta_imageImg = $request->file('meta_image');
        if ($meta_imageImg) {
            $product->meta_image = StorageHelper::store($meta_imageImg, 'admin/products/meta');
        }

        $product->ProductSku = $this->sku();
        $product->ProductWholesalePrice = $request->ProductWholesalePrice;
        $product->ProductResellerPrice = $request->ProductResellerPrice;
        $product->ProductRegularPrice = $request->ProductRegularPrice;
        $product->ProductSalePrice = $request->ProductSalePrice;
        $product->Discount = $request->Discount ?? 0;
        $product->min_sell_price = $request->min_sell_price;

        $product->qty = $request->qty;
        $product->low_stock = $request->low_stock ?? 0;
        if (isset($request->show_stock)) {
            $product->show_stock = 'On';
        } else {
            $product->show_stock = 'Off';
        }
        if (isset($request->show_stock_text)) {
            $product->show_stock_text = 'On';
        } else {
            $product->show_stock_text = 'Off';
        }
        if (isset($request->show_new_product)) {
            $product->show_new_product = 'On';
        } else {
            $product->show_new_product = 'Off';
        }

        if (isset($request->hot_list)) {
            $product->hot_list = 'On';
        } else {
            $product->hot_list = 'Off';
        }
        if (isset($request->ready_bost)) {
            $product->ready_bost = 'On';
        } else {
            $product->ready_bost = 'Off';
        }
        if (isset($request->profitable)) {
            $product->profitable = 'On';
        } else {
            $product->profitable = 'Off';
        }
        if (isset($request->limited)) {
            $product->limited = 'On';
        } else {
            $product->limited = 'Off';
        }
        if (isset($request->summer)) {
            $product->summer = 'On';
        } else {
            $product->summer = 'Off';
        }
        $product->shipping_days = $request->shipping_days;

        $product->ex_pack = $request->ex_pack ?? 0;
        $product->ex_dvc = $request->ex_dvc ?? 0;
        $product->extra_delivery_per_qty = $request->extra_delivery_per_qty ?? 0;

        if (isset($request->mart_status)) {
            $product->mart_status = 'On';
        } else {
            $product->mart_status = 'Off';
        }
        if (isset($request->reseller_status)) {
            $product->reseller_status = 'On';
        } else {
            $product->reseller_status = 'Off';
        }
        $product->reseller_bonus = $request->reseller_bonus ?? 0;
        $product->selling_type = $request->input('selling_type', 'both');

        $result = $product->save();

        if ($result) {
            $latestStock = new Stock();
            $latestStock->product_id = $product->id;
            $latestStock->purchase = 0;
            $latestStock->stock = $request->qty;
            $latestStock->save();
            $purchase = new Purchase();
            $purchase->date = date('Y-m-d');
            $purchase->invoiceID = date('Y-m-d');
            $purchase->product_id = $product->id;
            $purchase->supplier_id = 1;
            $purchase->quantity = $request->qty;
            $purchase->save();

            // Save price tiers
            if ($request->has('tiers')) {
                foreach ($request->tiers as $tier) {
                    if (!empty($tier['min_qty']) && !empty($tier['unit_price'])) {
                        ProductPriceTier::create([
                            'product_id' => $product->id,
                            'variant_title' => $tier['variant_title'] ?? null,
                            'min_qty' => $tier['min_qty'],
                            'max_qty' => $tier['max_qty'] ?? null,
                            'unit_price' => $tier['unit_price'],
                            'delivery_charge' => $tier['delivery_charge'] ?? 0,
                        ]);
                    }
                }
            }
        }
        if (isset($request->shop_id)) {
            return redirect('admin/shop/products')->with('success', 'Product updated successfully.');
        } else {
            return redirect('admin/products')->with('success', 'Product updated successfully.');
        }
    }

    public function sku()
    {
        $lastProduct = Product::latest('id')->first();
        if ($lastProduct) {
            $ProductID = $lastProduct->id + 1;
        } else {
            $ProductID = 1;
        }

        return 'SP#000' . $ProductID;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function productdata(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $isFull = $admin && $admin->isFullAdmin();

        // Admin products page should show only admin-created products (exclude vendor products).
        if ($isFull || ($admin->type !== 'Shop' && $admin->hasDirectPermission('product.view'))) {
            $products = Product::query()->whereNull('vendor_id');
        } else {
            $products = Product::where('shop_id', $admin->id)->whereNull('vendor_id');
        }

        if (isset($request->search) && $request->search != '') {
            $products = $products->where('ProductName', 'LIKE', '%' . $request->search . '%');
        }

        return Datatables::of($products)
            ->addColumn('action', function ($products) use ($admin, $isFull) {
                $a = '';
                if ($isFull || $admin->hasDirectPermission('product.edit')) {
                    $a .= '<a href="products/' . $products->id . '/edit" class="btn btn-primary btn-sm" style="margin-bottom:2px;"><i class="bi bi-pencil-square"></i></a> ';
                }
                if ($isFull || $admin->hasDirectPermission('product.delete')) {
                    $a .= '<a href="#" type="button" style="margin-bottom:2px;" id="deleteProductBtn" data-id="' . $products->id . '" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>';
                }
                return $a ?: '<span class="text-muted" style="font-size:12px;">View only</span>';
            })
            ->make(true);
    }
    public function productshopdata()
    {
        $products = Product::where('shop_id', '!=', Auth::guard('admin')->user()->id)
            ->where(function ($query) {
                $query->whereNull('vendor_id')
                      ->orWhere('vendor_approval_status', 'approved');
            })
            ->get();
        $admin = Auth::guard('admin')->user();
        $isFull = $admin && $admin->isFullAdmin();
        return Datatables::of($products)
            ->addColumn('action', function ($products) use ($admin, $isFull) {
                $a = '';
                if ($isFull || $admin->hasDirectPermission('shop-product.edit')) {
                    $a .= '<a href="product-edit/' . $products->id . '" class="btn btn-primary btn-sm" style="margin-bottom:2px;"><i class="bi bi-pencil-square"></i></a> ';
                }
                if ($isFull || $admin->hasDirectPermission('shop-product.delete')) {
                    $a .= '<a href="#" type="button" style="margin-bottom:2px;" id="deleteProductBtn" data-id="' . $products->id . '" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>';
                }
                return $a ?: '<span class="text-muted" style="font-size:12px;">View only</span>';
            })
            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $shop = 'No';
        $product = Product::with(['subcategories', 'minicategories', 'priceTiers', 'varients.sizes.bulkPrices'])->where('id', $id)->first();

        $sizes = Attrvalue::where('attribute_id', 2)->where('status', 'Active')->get();
        $colors = Attrvalue::where('attribute_id', 3)->where('status', 'Active')->get();
        $weights = Attrvalue::where('attribute_id', 1)->where('status', 'Active')->get();
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'status')->get();
        $brands = Brand::where('status', 'Active')->select('id', 'brand_name')->get();
        return view('backend.content.product.edit', ['shop' => $shop, 'product' => $product, 'weights' => $weights, 'colors' => $colors, 'sizes' => $sizes, 'categories' => $categories, 'brands' => $brands]);
    }

    public function editproduct($id)
    {
        $shop = 'Yes';
        $product = Product::with(['subcategories', 'minicategories', 'priceTiers', 'varients.sizes.bulkPrices'])->where('id', $id)->first();

        $sizes = Attrvalue::where('attribute_id', 2)->where('status', 'Active')->get();
        $colors = Attrvalue::where('attribute_id', 3)->where('status', 'Active')->get();
        $weights = Attrvalue::where('attribute_id', 1)->where('status', 'Active')->get();
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'status')->get();
        $brands = Brand::where('status', 'Active')->select('id', 'brand_name')->get();
        return view('backend.content.product.edit', ['shop' => $shop, 'product' => $product, 'weights' => $weights, 'colors' => $colors, 'sizes' => $sizes, 'categories' => $categories, 'brands' => $brands]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::where('id', $id)->first();
        $oldVendorApprovalStatus = $product->vendor_approval_status;
        $oldStatus = $product->status;
        $oldProductName = $product->ProductName;
        // For vendor products, preserve vendor_id and do not overwrite shop_id
        if (!$product->vendor_id) {
            if (isset($request->shop_id)) {
                $product->shop_id = $request->shop_id;
            } else {
                if (Auth::guard('admin')->user()->type == 'Shop') {
                    $product->shop_id = Auth::guard('admin')->user()->id;
                } else {
                    $shop = Admin::where('id', Auth::guard('admin')->user()->add_by)->first();
                    $product->shop_id = $shop->id;
                }
            }
        }
        $product->category_id = $request->category_id;
        $product->subcategory_id = $request->subcategory_id;
        $product->minicategory_id = $request->minicategory_id;
        $product->brand_id = $request->brand_id;

        $product->ProductName = $request->ProductName;
        $product->product_weight = $request->product_weight;
        $product->minimum_qty = $request->minimum_qty;

        $time = microtime('.') * 10000;

        $productImg = $request->file('ProductImage');
        if ($productImg) {
            $fullUrl = StorageHelper::store($productImg, 'admin/products');
            $product->ProductImage = $fullUrl;
            $product->ViewProductImage = $fullUrl;
        } elseif ($request->input('remove_thumbnail') == '1') {
            $product->ProductImage = null;
            $product->ViewProductImage = null;
        }
        $product->youtube_link = $request->youtube_link;

        // Start with existing gallery images
        $imageData = $product->PostImage ? json_decode($product->PostImage, true) : [];

        // Remove images marked for deletion
        if ($request->filled('removed_gallery_images')) {
            $removedImages = json_decode($request->removed_gallery_images, true) ?? [];
            $imageData = array_values(array_filter($imageData, function ($img) use ($removedImages) {
                return !in_array($img, $removedImages);
            }));
        }

        // Add newly uploaded images
        if ($request->hasFile('PostImage')) {
            foreach ($request->file('PostImage') as $imgfiles) {
                $imageData[] = StorageHelper::store($imgfiles, 'admin/products/gallery');
            }
        }

        $product->PostImage = !empty($imageData) ? json_encode(array_values($imageData)) : null;

        if ($request->color) {
            $product->color = json_encode($request->color);
        }
        if ($request->size) {
            $product->size = json_encode($request->size);
        }
        if ($request->weight) {
            $product->weight = json_encode($request->weight);
        }

        $product->ProductBreaf = $request->ProductBreaf;
        $product->ProductDetails = $request->ProductDetails;

        $product->MetaTitle = $request->MetaTitle;
        $product->MetaKey = $request->MetaKey;
        $product->MetaDescription = $request->MetaDescription;
        $meta_imageImg = $request->file('meta_image');
        if ($meta_imageImg) {
            $product->meta_image = StorageHelper::store($meta_imageImg, 'admin/products/meta');
        }

        // Preserve SKU for vendor products; regenerate for others
        if ($product->vendor_id) {
            $product->ProductSku = $request->ProductSku ?? $product->ProductSku;
        } else {
            $product->ProductSku = $this->sku();
        }
        $product->ProductWholesalePrice = $request->ProductWholesalePrice;
        $product->ProductResellerPrice = $request->ProductResellerPrice;
        $product->ProductRegularPrice = $request->ProductRegularPrice;
        $product->ProductSalePrice = $request->ProductSalePrice;
        $product->Discount = $request->Discount;
        $product->min_sell_price = $request->min_sell_price;

        $product->qty = $request->qty;
        $product->low_stock = $request->low_stock;
        if (isset($request->show_stock)) {
            $product->show_stock = 'On';
        } else {
            $product->show_stock = 'Off';
        }
        if (isset($request->show_stock_text)) {
            $product->show_stock_text = 'On';
        } else {
            $product->show_stock_text = 'Off';
        }
        // Admin can set vendor approval status when editing a vendor product
        if ($product->vendor_id && $request->has('vendor_approval_status')) {
            $product->vendor_approval_status = $request->vendor_approval_status;
        }
        if (isset($request->show_new_product)) {
            $product->show_new_product = 'On';
        } else {
            $product->show_new_product = 'Off';
        }

        if (isset($request->hot_list)) {
            $product->hot_list = 'On';
        } else {
            $product->hot_list = 'Off';
        }
        if (isset($request->ready_bost)) {
            $product->ready_bost = 'On';
        } else {
            $product->ready_bost = 'Off';
        }
        if (isset($request->profitable)) {
            $product->profitable = 'On';
        } else {
            $product->profitable = 'Off';
        }
        if (isset($request->limited)) {
            $product->limited = 'On';
        } else {
            $product->limited = 'Off';
        }
        if (isset($request->summer)) {
            $product->summer = 'On';
        } else {
            $product->summer = 'Off';
        }

        $product->shipping_days = $request->shipping_days;

        $product->ex_pack = $request->ex_pack;
        $product->ex_dvc = $request->ex_dvc;
        $product->extra_delivery_per_qty = $request->extra_delivery_per_qty ?? 0;

        if (isset($request->mart_status)) {
            $product->mart_status = 'On';
        } else {
            $product->mart_status = 'Off';
        }
        if (isset($request->reseller_status)) {
            $product->reseller_status = 'On';
        } else {
            $product->reseller_status = 'Off';
        }
        $product->reseller_bonus = $request->reseller_bonus;
        $product->selling_type = $request->input('selling_type', $product->selling_type ?? 'both');

        $product->update();

        // Replace price tiers
        if ($request->has('tiers')) {
            ProductPriceTier::where('product_id', $product->id)->delete();
            foreach ($request->tiers as $tier) {
                if (!empty($tier['min_qty']) && !empty($tier['unit_price'])) {
                    ProductPriceTier::create([
                        'product_id' => $product->id,
                        'variant_title' => $tier['variant_title'] ?? null,
                        'min_qty' => $tier['min_qty'],
                        'max_qty' => $tier['max_qty'] ?? null,
                        'unit_price' => $tier['unit_price'],
                        'delivery_charge' => $tier['delivery_charge'] ?? 0,
                    ]);
                }
            }
        }

        if ($product->vendor_id) {
            $product->loadMissing('vendor.user');

            $messages = [];
            if ($request->has('vendor_approval_status') && $oldVendorApprovalStatus !== $product->vendor_approval_status) {
                $messages[] = 'Approval status changed to "' . $product->vendor_approval_status . '"';
            }
            if ($oldStatus !== $product->status) {
                $messages[] = 'Publish status changed to "' . $product->status . '"';
            }
            if ($oldProductName !== $product->ProductName) {
                $messages[] = 'Product name updated to "' . $product->ProductName . '"';
            }

            if (!empty($messages) && $product->vendor) {
                $this->vendorNotificationService->notifyVendor(
                    $product->vendor,
                    'Product updated by SelfShop',
                    'Your product "' . $product->ProductName . '" has been updated by SelfShop. ' . implode('. ', $messages) . '.',
                    'info',
                    [
                        'event' => 'vendor_product_updated_by_admin',
                        'product_id' => $product->id,
                        'product_name' => $product->ProductName,
                        'changes' => $messages,
                    ],
                    '/vendor/products'
                );
            }
        }

        if ($product) {
            if (isset($request->shop_id)) {
                return redirect('admin/shop/products')->with('message', 'Product updated successfully.');
            } else {
                return redirect('admin/products')->with('message', 'Product updated successfully.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        if ($product->ProductImage) {
            // unlink($product->ProductImage);
        }
        $product->delete();
        return response()->json('success', 200);
    }
}
