<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Faq;
use Illuminate\Http\Request;
use DataTables;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.content.faq.index');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $faq= new Faq();
        $faq->question =$request->question;
        $faq->answer =$request->answer;
        $faq->youtube_embade =$request->youtube_embade;
        $faq->save();
        return response()->json($faq, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function faqdata()
    {
        $faq = Faq::all();
        return Datatables::of($faq)
            ->addColumn('action', function ($faq) {
                return '<a href="#" type="button" id="editFaqBtn" data-id="' . $faq->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainFaq" ><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deleteFaqBtn" data-id="' . $faq->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })

            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function edit( $id)
    {
        $faq =Faq::findOrfail($id);
        return response()->json($faq, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $faq =Faq::findOrfail($id);
        $faq->question =$request->question;
        $faq->answer =$request->answer;
        $faq->youtube_embade =$request->youtube_embade;
        $faq->update();
        return response()->json($faq, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $faq =Faq::where('id',$id)->first();
        $faq->delete();
        return response()->json('success', 200);
    }

    public function updatestatus(Request $request)
    {
        $faq =Faq::where('id',$request->faq_id)->first();
        $faq->status=$request->status;
        $faq->update();
        return response()->json($faq, 200);
    }
}
