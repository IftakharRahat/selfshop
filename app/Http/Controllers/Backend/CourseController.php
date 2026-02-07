<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Course; 
use Illuminate\Http\Request;
use DataTables;

class CourseController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.content.course.course');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $course =new Course();
        $course->course_name =$request->course_name;
        $course->coursecategory_id =$request->coursecategory_id;
        $course->youtube_embade =$request->youtube_embade;
        $course_image = $request->file('course_image');
        $name = time() . "_" . $course_image->getClientOriginalName();
        $uploadPath = ('public/images/course/'); 
        $course_image->move($uploadPath, $name);
        $course_imageImgUrl = $uploadPath . $name;
        $webp = $course_imageImgUrl;
        $im = imagecreatefromstring(file_get_contents($webp));
        $new_webp = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $webp);
        imagewebp($im, $new_webp, 50);
        $course->course_image = $new_webp; 
        $course->save();
        return response()->json($course, 200);
    }

    public function coursedata()
    {
        $courses = Course::all();
        return Datatables::of($courses)
            ->addColumn('action', function ($courses) {
                return '<a href="#" type="button" id="editCourseBtn" data-id="' . $courses->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainCourse" ><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deleteCourseBtn" data-id="' . $courses->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })

            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $course = Course::findOrfail($id);
        return response()->json($course, 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrfail($id);
        $course->course_name =$request->course_name;
        $course->coursecategory_id =$request->coursecategory_id;
        $course->youtube_embade =$request->youtube_embade;
        if($request->course_image){
            unlink($course->course_image);
            $course_image = $request->file('course_image');
            $name = time() . "_" . $course_image->getClientOriginalName();
            $uploadPath = ('public/images/course/'); 
            $course_image->move($uploadPath, $name);
            $course_imageImgUrl = $uploadPath . $name; 
            $webp = $course_imageImgUrl;
            $im = imagecreatefromstring(file_get_contents($webp));
            $new_webp = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $webp);
            imagewebp($im, $new_webp, 50);
            $course->course_image = $new_webp;
        }
        $course->save();
        return response()->json($course, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $course = Course::findOrfail($id);
        $course->delete();
        return response()->json('success', 200);
    }

    public function updatestatus(Request $request)
    {
        $course = Course::where('id',$request->category_id)->first();
        if(isset($request->status)){
            $course->status=$request->status;
        }
        $course->update();
        return response()->json($course, 200);
    }
}