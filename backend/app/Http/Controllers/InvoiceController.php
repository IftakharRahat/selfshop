<?php

namespace App\Http\Controllers;

use App\Exports\OrderExport;
use App\Exports\OrderdataExport;
use App\Models\Courier;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class InvoiceController extends Controller
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
    public function fileExport(Request $request)
    {
        $status =$request->status;
        if($status){
            return Excel::download(new OrderExport($status), date('Y-m-d').'order.xlsx');
        }else{
            return redirect()->back()->with('error','Please Select Any Status');
        }
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
        //
    }

    public function downloadexcle(Request $request)
    {
        $orders_id =$request->order_id;
        $orders_id = explode(',' ,$orders_id);
        if($orders_id){
            return Excel::download(new OrderdataExport($orders_id), 'orderdata.xlsx');
        }else{
            return redirect()->back()->with('error','Please Select Any Orders');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}