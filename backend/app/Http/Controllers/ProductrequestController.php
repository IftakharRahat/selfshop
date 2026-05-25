<?php

namespace App\Http\Controllers;

use App\Models\Productrequest;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use DB;

class ProductrequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products=Productrequest::where('from_id',Auth::user()->id)->get()->reverse();
        return view('frontend.content.productrequest.index',['products'=>$products]);
    }

    public function admindex($status)
    {
        return view('backend.content.productrequest.index',['status'=>$status]);
    }

    public function produtrqdata($status)
    {
        if($status ==='productrequestall'){
            $products = Productrequest::with('users');
        }else{
            $products = Productrequest::with('users')->where('status', '=', $status);
        }
        return Datatables::of($products->orderBy('productrequests.id', 'DESC'))
            ->editColumn('user', function ($products) {
                if ($products->users) {
                    return $products->users->name;
                } else {
                    return 'user not assign';
                }
            })
            ->addColumn('action', function ($products) {
                return '<a href="#" type="button" id="editRqBtn" data-id="' . $products->id . '" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainPrq"><i class="bi bi-pencil-square"></i></a> <a href="#" type="button" class="btn btn-danger btn-sm deleteRqBtn" data-id="' . $products->id . '"><i class="bi bi-trash"></i></a>';
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
        $product=new Productrequest();
        $productImg = $request->file('attachment');
        $time = microtime('.') * 10000;
        if($productImg){
            $imgname = $time . $productImg->getClientOriginalName();
            $imguploadPath = ('public/images/user/profile/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $product->attachment = $productImgUrl;
        }
        $id=Auth::user()->id;
        $product->from_id=$id;
        $product->p_name=$request->p_name;
        if (Schema::hasColumn('productrequests', 'p_quantity')) {
            $product->p_quantity = $request->p_quantity;
        }
        if (Schema::hasColumn('productrequests', 'p_description')) {
            $product->p_description = $request->p_description;
        }
        if (Schema::hasColumn('productrequests', 'message')) {
            $product->message = $request->p_description ?? $request->message;
        }
        $product->save();
        return redirect()->back()->with('success','Product request submitted successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Productrequest  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function show(Productrequest $productrequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Productrequest  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $product=Productrequest::where('id',$id)->first();
        return response()->json($product, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Productrequest  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product=Productrequest::where('id',$id)->first();

        $productImg = $request->file('attachment');
        $time = microtime('.') * 10000;
        if($productImg){
            $imgname = $time . $productImg->getClientOriginalName();
            $imguploadPath = ('public/images/user/profile/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $product->attachment = $productImgUrl;
        }
        $product->p_name=$request->p_name;
        $product->status=$request->status;
        if (Schema::hasColumn('productrequests', 'p_quantity') && $request->filled('p_quantity')) {
            $product->p_quantity = $request->p_quantity;
        }
        if (Schema::hasColumn('productrequests', 'p_description') && $request->filled('p_description')) {
            $product->p_description = $request->p_description;
        }
        if (Schema::hasColumn('productrequests', 'message')) {
            $product->message = $request->message ?? $request->p_description;
        }
        $product->save();
        return response()->json($product, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Productrequest  $productrequest
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Productrequest::findOrFail($id);
        $product->delete();
        return response()->json(['status' => 'success', 'message' => 'Product request deleted successfully.']);
    }
}
