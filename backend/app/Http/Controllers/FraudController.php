<?php

namespace App\Http\Controllers;

use App\Models\Fraud;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Auth;
use DB;
class FraudController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $fraudlists=Fraud::where('from_id',Auth::user()->id)->get()->reverse();
        return view('frontend.content.productrequest.fraud',['fraudlists'=>$fraudlists]);
    }

    public function checkfraud($slug)
    {
        $frauds=Fraud::where('phone','LIKE',"%{$slug}%")->get();
        return view('frontend.content.productrequest.fraudlist',['frauds'=>$frauds]);
    }

    public function admindex($status)
    {
        return view('backend.content.fraud.index',['status'=>$status]);
    }

    public function frauddata($status)
    {
        if($status ==='allfraud'){
            $frauds = Fraud::with('users');
        }else{
            $frauds = Fraud::with('users')->where('status', '=', $status);
        }
        return Datatables::of($frauds->orderBy('frauds.id', 'DESC'))
            ->editColumn('user', function ($frauds) {
                if ($frauds->users) {
                    return $frauds->users->name;
                } else {
                    return 'user not assign';
                }
            })
            ->addColumn('action', function ($frauds) {
                return '<a href="#" type="button" id="editFrdBtn" data-id="' . $frauds->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainFrd" ><i class="bi bi-pencil-square"></i></a>';
            })
            ->make(true);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product=new Fraud();
        $id=Auth::user()->id;
        $product->from_id=$id;
        $product->phone=$request->phone;
        $product->message=$request->message;
        $product->save();
        return redirect()->back()->with('success','Fraud check request give successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Fraud  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function show(Fraud $productrequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Fraud  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function edit( $id)
    {
        $fraud=Fraud::where('id',$id)->first();
        return response()->json($fraud, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Fraud  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fraud=Fraud::where('id',$id)->first();
        $fraud->phone=$request->phone;
        $fraud->status=$request->status;
        $fraud->message=$request->message;
        $fraud->save();
        return response()->json($fraud, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Fraud  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fraud $productrequest)
    {
        //
    }
}
