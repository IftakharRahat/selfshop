<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DataTables;

class BrandController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.content.brand.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $brand =new Brand();
        $brand->brand_name =$request->brand_name;
        $brand_icon = $request->file('brand_icon');
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
        $safeName = Str::slug(pathinfo($brand_icon->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . Str::random(8) . '.' . $brand_icon->getClientOriginalExtension();
        $path = $brand_icon->storeAs('admin/brands', $safeName, 'r2');
        $brand->brand_icon = $r2BaseUrl . '/' . $path;
        $brand->save();
        return response()->json($brand, 200);
    }

    public function branddata()
    {
        $brands = Brand::all();
        $admin = \Auth::guard('admin')->user();
        $isFull = $admin && $admin->isFullAdmin();
        return Datatables::of($brands)
            ->addColumn('action', function ($brands) use ($admin, $isFull) {
                $a = '';
                if ($isFull || $admin->hasDirectPermission('category.edit')) {
                    $a .= '<a href="#" type="button" id="editBrandBtn" data-id="' . $brands->id . '" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainBrand"><i class="bi bi-pencil-square"></i></a> ';
                }
                if ($isFull || $admin->hasDirectPermission('category.delete')) {
                    $a .= '<a href="#" type="button" id="deleteBrandBtn" data-id="' . $brands->id . '" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>';
                }
                return $a ?: '<span class="text-muted" style="font-size:12px;">View only</span>';
            })
            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $brand = Brand::findOrfail($id);
        return response()->json($brand, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $brand = Brand::findOrfail($id);
        $brand->brand_name =$request->brand_name;
        if($request->brand_icon){
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $brand_icon = $request->file('brand_icon');
            $safeName = Str::slug(pathinfo($brand_icon->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $brand_icon->getClientOriginalExtension();
            $path = $brand_icon->storeAs('admin/brands', $safeName, 'r2');
            $brand->brand_icon = $r2BaseUrl . '/' . $path;
        }
        $brand->save();
        return response()->json($brand, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $brand = Brand::findOrfail($id);
        $brand->delete();
        return response()->json('success', 200);
    }

    public function statusupdate(Request $request)
    {
        $brand = Brand::where('id',$request->brand_id)->first();
        $brand->status=$request->status;
        $brand->update();
        return response()->json($brand, 200);
    }
}
