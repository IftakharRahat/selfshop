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
use App\Models\Varient;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
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
            $imgname = $time . random_int(100000, 999999);
            $imguploadPath = ('public/images/product/image/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $product->ProductImage = $productImgUrl;
            $webp = $productImgUrl;
            $im = imagecreatefromstring(file_get_contents($webp));
            $new_webp = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $webp);
            imagewebp($im, $new_webp, 50);
            $product->ViewProductImage = $new_webp;
        }
        $product->youtube_link = $request->youtube_link;
        if ($request->hasFile('PostImage')) {
            foreach ($request->file('PostImage') as $imgfiles) {
                $name = time() . "_" . random_int(100000, 999999);
                $imgfiles->move(public_path() . '/images/product/slider/', $name);
                $imageData[] = $name;
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
            $metaimgname = $time . random_int(100000, 999999);
            $metaimguploadPath = ('public/images/meta_image/');
            $meta_imageImg->move($metaimguploadPath, $metaimgname);
            $meta_imageImgUrl = $metaimguploadPath . $metaimgname;
            $product->meta_image = $meta_imageImgUrl;
        }

        $product->ProductSku = $this->sku();
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
        }
        if (isset($request->shop_id)) {
            return redirect('admin/shop/products')->with('success', 'Product update successfully');
        } else {
            return redirect('admin/products')->with('success', 'Product update successfully');
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
        if (isset($request->search)) {
            $products = Product::where('shop_id', Auth::guard('admin')->user()->id)->where('ProductName', 'LIKE', '%' . $request->search . '%');
        } else {
            $products = Product::where('shop_id', Auth::guard('admin')->user()->id);
        }

        return Datatables::of($products)
            ->addColumn('action', function ($products) {
                return '<a href="product/add-varient/' . $products->id . '" class="btn btn-primary btn-sm" style="margin-bottom:2px;">Varient</a>
                <a href="products/' . $products->id . '/edit" class="btn btn-primary btn-sm" style="margin-bottom:2px;"><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" style="margin-bottom:2px;" id="deleteProductBtn" data-id="' . $products->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })

            ->make(true);
    }
    public function productshopdata()
    {
        $products = Product::where('shop_id', '!=', Auth::guard('admin')->user()->id)->get();
        return Datatables::of($products)
            ->addColumn('action', function ($products) {
                return '<a href="../product/add-varient/' . $products->id . '" class="btn btn-primary btn-sm" style="margin-bottom:2px;">Varient</a>
                <a href="product-edit/' . $products->id . '" class="btn btn-primary btn-sm" style="margin-bottom:2px;"><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" style="margin-bottom:2px;" id="deleteProductBtn" data-id="' . $products->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
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
        $product = Product::with(['subcategories', 'minicategories'])->where('id', $id)->first();

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
        $product = Product::with(['subcategories', 'minicategories'])->where('id', $id)->first();

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
            // unlink($product->ProductImage);
            // unlink($product->ViewProductImage);
            $imgname = $time . random_int(100000, 999999);
            $imguploadPath = ('public/images/product/image/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $product->ProductImage = $productImgUrl;
            $webp = $productImgUrl;
            $im = imagecreatefromstring(file_get_contents($webp));
            $new_webp = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $webp);
            imagewebp($im, $new_webp, 50);
            $product->ViewProductImage = $new_webp;
        }
        $product->youtube_link = $request->youtube_link;

        if ($request->hasFile('PostImage')) {
            if ($product->PostImage) {
                foreach (json_decode($product->PostImage) as $postimg) {
                    unlink('public/images/product/slider/' . $postimg);
                }
            }
            foreach ($request->file('PostImage') as $imgfiles) {
                $name = time() . "_" . random_int(100000, 999999);
                $imgfiles->move(public_path() . '/images/product/slider/', $name);
                $imageData[] = $name;
            }
            $product->PostImage = json_encode($imageData);
        }

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
            $metaimgname = $time . $meta_imageImg->getClientOriginalName();
            $metaimguploadPath = ('public/images/meta_image/');
            $meta_imageImg->move($metaimguploadPath, $metaimgname);
            $meta_imageImgUrl = $metaimguploadPath . $metaimgname;
            $product->meta_image = $meta_imageImgUrl;
        }

        $product->ProductSku = $this->sku();
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


        $product->update();

        if ($product) {
            if (isset($request->shop_id)) {
                return redirect()->back()->with('success', 'Product update successfully');
            } else {
                return redirect()->back()->with('success', 'Product update successfully');
            }
        } else {
            return redirect()->back()->with('success', 'something went wrong');
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
