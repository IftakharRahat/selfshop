<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Coursecategory; 
use Illuminate\Http\Request;
use DataTables;

class CoursecategoryController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.content.coursecategory.coursecategory');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $coursecategory =new Coursecategory();
        $coursecategory->coursecategory_name =$request->coursecategory_name;
        $coursecategory->youtube_embade =$request->youtube_embade;
        $coursecategory_image = $request->file('coursecategory_image');
        $name = time() . "_" . $coursecategory_image->getClientOriginalName();
        $uploadPath = ('public/images/coursecategory/'); 
        $coursecategory_image->move($uploadPath, $name);
        $coursecategory_imageImgUrl = $uploadPath . $name;
        $webp = $coursecategory_imageImgUrl;
        $im = imagecreatefromstring(file_get_contents($webp));
        $new_webp = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $webp);
        imagewebp($im, $new_webp, 50);
        $coursecategory->coursecategory_image = $new_webp; 
        $coursecategory->save();
        return response()->json($coursecategory, 200);
    }

    public function coursecategorydata()
    {
        $coursecategorys = Coursecategory::all();
        return Datatables::of($coursecategorys)
            ->addColumn('action', function ($coursecategorys) {
                return '<a href="#" type="button" id="editCoursecategoryBtn" data-id="' . $coursecategorys->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainCoursecategory" ><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deleteCoursecategoryBtn" data-id="' . $coursecategorys->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })

            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Coursecategory  $coursecategory
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $coursecategory = Coursecategory::findOrfail($id);
        return response()->json($coursecategory, 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Coursecategory  $coursecategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $coursecategory = Coursecategory::findOrfail($id);
        $coursecategory->coursecategory_name =$request->coursecategory_name;
        $coursecategory->youtube_embade =$request->youtube_embade;
        if($request->coursecategory_image){
            unlink($coursecategory->coursecategory_image);
            $coursecategory_image = $request->file('coursecategory_image');
            $name = time() . "_" . $coursecategory_image->getClientOriginalName();
            $uploadPath = ('public/images/coursecategory/'); 
            $coursecategory_image->move($uploadPath, $name);
            $coursecategory_imageImgUrl = $uploadPath . $name; 
            $webp = $coursecategory_imageImgUrl;
            $im = imagecreatefromstring(file_get_contents($webp));
            $new_webp = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $webp);
            imagewebp($im, $new_webp, 50);
            $coursecategory->coursecategory_image = $new_webp;
        }
        $coursecategory->save();
        return response()->json($coursecategory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Coursecategory  $coursecategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $coursecategory = Coursecategory::findOrfail($id);
        $coursecategory->delete();
        return response()->json('success', 200);
    }

    public function updatestatus(Request $request)
    {
        $coursecategory = Coursecategory::where('id',$request->category_id)->first();
        if(isset($request->status)){
            $coursecategory->status=$request->status;
        }
        $coursecategory->update();
        return response()->json($coursecategory, 200);
    }
}