<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Minicategory;
use App\Models\Category;
use Illuminate\Http\Request;
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
        $name = time() . "_" . $minicategory_icon->getClientOriginalName();
        $uploadPath = ('public/images/minicategory/');
        $minicategory_icon->move($uploadPath, $name);
        $minicategory_iconImgUrl = $uploadPath . $name;
        $minicategory->minicategory_icon = $minicategory_iconImgUrl;
        $minicategory->save();
        return response()->json($minicategory, 200);
    }

    public function minicategorydata()
    {
        $minicategorys = Minicategory::with(['categories','subcategories'])->get();
        return Datatables::of($minicategorys)
            ->addColumn('action', function ($minicategorys) {
                return '<a href="#" type="button" id="editMinicategoryBtn" data-id="' . $minicategorys->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainMinicategory" ><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deleteMinicategoryBtn" data-id="' . $minicategorys->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
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
            if(isset($minicategory->minicategory_icon)){
                unlink($minicategory->minicategory_icon);
            }
            $minicategory_icon = $request->file('minicategory_icon');
            $name = time() . "_" . $minicategory_icon->getClientOriginalName();
            $uploadPath = ('public/images/category/');
            $minicategory_icon->move($uploadPath, $name);
            $minicategory_iconImgUrl = $uploadPath . $name;
            $minicategory->minicategory_icon = $minicategory_iconImgUrl;
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
