<?php

namespace App\Http\Controllers;

use App\Models\Varient;
use Illuminate\Http\Request;

class VarientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $category = new Varient();
        $category->product_id = $request->product_id;
        $category->title = $request->title;
        $category->qty = $request->qty;
        $category->price = $request->price;
        $category->extra_delivery_charge = $request->extra_delivery_charge;
        $category->save();
        return response()->json($category, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function show(Varient $varient)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = Varient::findOrfail($id);
        return response()->json($category, 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = Varient::findOrfail($id);
        $category->title = $request->title;
        $category->qty = $request->qty;
        $category->price = $request->price;
        $category->extra_delivery_charge = $request->extra_delivery_charge;
        $category->status = $request->status;
        $category->save();
        return response()->json($category, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function destroy(Varient $varient)
    {
        $varient->delete();
        return response()->json('success', 200);
    }
}
