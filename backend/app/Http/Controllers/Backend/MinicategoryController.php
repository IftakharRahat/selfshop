<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Minicategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DataTables;

class MinicategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories =Category::where('status','Active')->get();
        return view('backend.content.minicategory.index',['categories'=>$categories]);
    }



    public function store(Request $request)
    {
        $minicategory =new Minicategory();
        $minicategory->mini_category_name =$request->mini_category_name;
        $minicategory->category_id =$request->category_id;
        $minicategory->subcategory_id =$request->subcategory_id;
        $minicategory_icon = $request->file('minicategory_icon');
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
        $safeName = Str::slug(pathinfo($minicategory_icon->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . Str::random(8) . '.' . $minicategory_icon->getClientOriginalExtension();
        $path = $minicategory_icon->storeAs('admin/minicategories', $safeName, 'r2');
        $minicategory->minicategory_icon = $r2BaseUrl . '/' . $path;
        $minicategory->save();
        return response()->json($minicategory, 200);
    }

    public function minicategorydata()
    {
        $minicategorys = Minicategory::with(['categories','subcategories'])->get();
        $admin = \Auth::guard('admin')->user();
        $isFull = $admin && $admin->isFullAdmin();
        return Datatables::of($minicategorys)
            ->addColumn('action', function ($minicategorys) use ($admin, $isFull) {
                $a = '';
                if ($isFull || $admin->hasDirectPermission('category.edit')) {
                    $a .= '<a href="#" type="button" id="editMinicategoryBtn" data-id="' . $minicategorys->id . '" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainMinicategory"><i class="bi bi-pencil-square"></i></a> ';
                }
                if ($isFull || $admin->hasDirectPermission('category.delete')) {
                    $a .= '<a href="#" type="button" id="deleteMinicategoryBtn" data-id="' . $minicategorys->id . '" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>';
                }
                return $a ?: '<span class="text-muted" style="font-size:12px;">View only</span>';
            })
            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Minicategory  $minicategory
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $minicategory = Minicategory::findOrfail($id);
        return response()->json($minicategory, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Minicategory  $minicategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $minicategory = Minicategory::findOrfail($id);
        $minicategory->mini_category_name =$request->mini_category_name;
        $minicategory->category_id =$request->category_id;

        if($request->minicategory_icon){
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $minicategory_icon = $request->file('minicategory_icon');
            $safeName = Str::slug(pathinfo($minicategory_icon->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $minicategory_icon->getClientOriginalExtension();
            $path = $minicategory_icon->storeAs('admin/minicategories', $safeName, 'r2');
            $minicategory->minicategory_icon = $r2BaseUrl . '/' . $path;
        }

        $minicategory->save();
        return response()->json($minicategory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Minicategory  $minicategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $minicategory = Minicategory::findOrfail($id);
        $minicategory->delete();
        return response()->json('success', 200);
    }

    public function statusupdate(Request $request)
    {
        $minicategory = Minicategory::where('id',$request->minicategory_id)->first();
        $minicategory->status=$request->status;
        $minicategory->update();
        return response()->json($minicategory, 200);
    }
}
