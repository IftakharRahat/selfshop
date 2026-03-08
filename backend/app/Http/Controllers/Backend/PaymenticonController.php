<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Paymenticon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DataTables;

class PaymenticonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.content.paymenticon.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $paymenticon =new Paymenticon();
        $paymenticon->payment_type_name =$request->payment_type_name;
        $payment_icon = $request->file('payment_icon');
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
        $safeName = Str::slug(pathinfo($payment_icon->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . Str::random(8) . '.' . $payment_icon->getClientOriginalExtension();
        $path = $payment_icon->storeAs('admin/paymenticons', $safeName, 'r2');
        $paymenticon->payment_icon = $r2BaseUrl . '/' . $path;
        $paymenticon->save();
        return response()->json($paymenticon, 200);
    }

    public function paymenticondata()
    {
        $paymenticons = Paymenticon::all();
        return Datatables::of($paymenticons)
            ->addColumn('action', function ($paymenticons) {
                return '<a href="#" type="button" id="editPaymenticonBtn" data-id="' . $paymenticons->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainPaymenticon" ><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deletePaymenticonBtn" data-id="' . $paymenticons->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })

            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Paymenticon  $paymenticon
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $paymenticon = Paymenticon::findOrfail($id);
        return response()->json($paymenticon, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Paymenticon  $paymenticon
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $paymenticon = Paymenticon::findOrfail($id);
        $paymenticon->payment_type_name =$request->payment_type_name;
        if($request->payment_icon){
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $payment_icon = $request->file('payment_icon');
            $safeName = Str::slug(pathinfo($payment_icon->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $payment_icon->getClientOriginalExtension();
            $path = $payment_icon->storeAs('admin/paymenticons', $safeName, 'r2');
            $paymenticon->payment_icon = $r2BaseUrl . '/' . $path;
        }
        $paymenticon->save();
        return response()->json($paymenticon, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Paymenticon  $paymenticon
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $paymenticon = Paymenticon::findOrfail($id);
        $paymenticon->delete();
        return response()->json('success', 200);
    }

    public function statusupdate(Request $request)
    {
        $paymenticon = Paymenticon::where('id',$request->paymenticon_id)->first();
        $paymenticon->status=$request->status;
        $paymenticon->update();
        return response()->json($paymenticon, 200);
    }
}