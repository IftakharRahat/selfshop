<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $flashSale->registration_deadline = $request->registration_deadline;
        $flashSale->vendor_registration = $request->has('vendor_registration') ? (bool) $request->vendor_registration : true;
        $flashSale->status = 'Active';

        if ($request->hasFile('banner_image')) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $file = $request->file('banner_image');
            $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('admin/campaigns', $safeName, 'r2');
            $flashSale->banner_image = $r2BaseUrl . '/' . $path;
        }

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
            ->addColumn('banner_preview', function ($sale) {
                if ($sale->banner_image) {
                    return '<img src="' . asset($sale->banner_image) . '" style="width:60px;height:35px;object-fit:cover;border-radius:4px;">';
                }
                return '<span class="text-muted">—</span>';
            })
            ->addColumn('reg_deadline', function ($sale) {
                return $sale->registration_deadline ? $sale->registration_deadline->format('d M Y, h:i A') : '—';
            })
            ->addColumn('vendor_reg', function ($sale) {
                if ($sale->vendor_registration) {
                    return '<button type="button" class="btn btn-success btn-sm vendorRegBtn" data-id="' . $sale->id . '" data-val="0">Open</button>';
                }
                return '<button type="button" class="btn btn-secondary btn-sm vendorRegBtn" data-id="' . $sale->id . '" data-val="1">Closed</button>';
            })
            ->addColumn('action', function ($sale) {
                return '<a href="#" type="button" id="editFlashSaleBtn" data-id="' . $sale->id . '" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editFlashSaleModal"><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="manageProductsBtn" data-id="' . $sale->id . '" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#manageProductsModal"><i class="bi bi-box-seam"></i></a>
                <a href="#" type="button" id="deleteFlashSaleBtn" data-id="' . $sale->id . '" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>';
            })
            ->rawColumns(['action', 'banner_preview', 'vendor_reg'])
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
        $flashSale->registration_deadline = $request->registration_deadline;
        if ($request->has('vendor_registration')) {
            $flashSale->vendor_registration = (bool) $request->vendor_registration;
        }

        if ($request->hasFile('banner_image')) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $file = $request->file('banner_image');
            $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('admin/campaigns', $safeName, 'r2');
            $flashSale->banner_image = $r2BaseUrl . '/' . $path;
        }

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

    public function vendorRegistrationUpdate(Request $request)
    {
        $flashSale = FlashSale::where('id', $request->flash_sale_id)->first();
        $flashSale->vendor_registration = (bool) $request->vendor_registration;
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

    public function getVendorSubmissions($id)
    {
        $products = FlashSaleProduct::where('flash_sale_id', $id)
            ->whereNotNull('vendor_id')
            ->with(['product:id,ProductName,ViewProductImage,ProductSalePrice', 'vendor:id,company_name'])
            ->get();
        return response()->json($products, 200);
    }

    public function searchProducts(Request $request)
    {
        $term = trim((string) $request->q);
        $query = Product::query()
            ->where('products.status', 'Active')
            ->leftJoin('subcategories', 'subcategories.id', '=', 'products.subcategory_id')
            ->select(
                'products.id',
                'products.ProductName',
                'products.ViewProductImage',
                'products.ProductSalePrice',
                'products.ProductRegularPrice',
                'subcategories.sub_category_name'
            );

        if ($term !== '') {
            $query->where('products.ProductName', 'LIKE', '%' . $term . '%');
            // Searching path can return more results, but still bounded.
            $query->limit(200);
        } else {
            // Initial dropdown load should be lightweight.
            $query->orderByDesc('products.id')->limit(60);
        }

        $products = $query->get()->groupBy(function ($product) {
            return $product->sub_category_name ?: 'Uncategorized';
        });

        return response()->json($products, 200);
    }
}
