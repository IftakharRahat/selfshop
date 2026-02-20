<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use DataTables;

class FlashSaleController extends Controller
{
    public function index()
    {
        return view('backend.content.flashsale.index');
    }

    public function store(Request $request)
    {
        if (empty($request->title)) {
            return response()->json('error', 200);
        }

        $flashSale = new FlashSale();
        $flashSale->title = $request->title;
        $flashSale->start_time = $request->start_time;
        $flashSale->end_time = $request->end_time;
        $flashSale->status = 'Active';
        $flashSale->save();

        return response()->json($flashSale, 200);
    }

    public function flashsaledata()
    {
        $flashSales = FlashSale::withCount('products')->get();
        return Datatables::of($flashSales)
            ->addColumn('product_count', function ($sale) {
                return $sale->products_count;
            })
            ->addColumn('action', function ($sale) {
                return '<a href="#" type="button" id="editFlashSaleBtn" data-id="' . $sale->id . '" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editFlashSaleModal"><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="manageProductsBtn" data-id="' . $sale->id . '" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#manageProductsModal"><i class="bi bi-box-seam"></i></a>
                <a href="#" type="button" id="deleteFlashSaleBtn" data-id="' . $sale->id . '" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit($id)
    {
        $flashSale = FlashSale::with('flashSaleProducts.product')->findOrFail($id);
        return response()->json($flashSale, 200);
    }

    public function update(Request $request, $id)
    {
        $flashSale = FlashSale::findOrFail($id);
        if (empty($request->title)) {
            return response()->json('error', 200);
        }
        $flashSale->title = $request->title;
        $flashSale->start_time = $request->start_time;
        $flashSale->end_time = $request->end_time;
        $flashSale->update();
        return response()->json($flashSale, 200);
    }

    public function destroy($id)
    {
        $flashSale = FlashSale::findOrFail($id);
        $flashSale->delete();
        return response()->json('success', 200);
    }

    public function statusupdate(Request $request)
    {
        $flashSale = FlashSale::where('id', $request->flash_sale_id)->first();
        $flashSale->status = $request->status;
        $flashSale->update();
        return response()->json($flashSale, 200);
    }

    // Product management
    public function addProduct(Request $request)
    {
        $exists = FlashSaleProduct::where('flash_sale_id', $request->flash_sale_id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($exists) {
            return response()->json(['error' => 'Product already in flash sale'], 400);
        }

        $fsp = new FlashSaleProduct();
        $fsp->flash_sale_id = $request->flash_sale_id;
        $fsp->product_id = $request->product_id;
        $fsp->discount_percentage = $request->discount_percentage ?? 0;
        $fsp->save();

        $fsp->load('product');
        return response()->json($fsp, 200);
    }

    public function removeProduct($id)
    {
        $fsp = FlashSaleProduct::findOrFail($id);
        $fsp->delete();
        return response()->json('success', 200);
    }

    public function getProducts($id)
    {
        $products = FlashSaleProduct::where('flash_sale_id', $id)
            ->with('product')
            ->get();
        return response()->json($products, 200);
    }

    public function searchProducts(Request $request)
    {
        $query = Product::where('status', 'Active')
            ->with('subcategories:id,sub_category_name');

        if ($request->q) {
            $query->where('ProductName', 'LIKE', '%' . $request->q . '%');
        }

        $products = $query->select('id', 'ProductName', 'ViewProductImage', 'ProductSalePrice', 'ProductRegularPrice', 'subcategory_id')
            ->orderBy('subcategory_id')
            ->get()
            ->groupBy(function ($product) {
                return $product->subcategories ? $product->subcategories->sub_category_name : 'Uncategorized';
            });

        return response()->json($products, 200);
    }
}
