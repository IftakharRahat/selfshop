<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Vencomment;
use App\Models\Withdrew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VencommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function accounts(Request $request)
    {
        if (isset($request->search)) {
            $blances = Vencomment::where('type', 'Deposit')->where('shop_id', Auth::guard('admin')->user()->id)->where('order_id', $request->search)->get();
        } else {
            $blances = Vencomment::where('type', 'Deposit')->where('shop_id', Auth::guard('admin')->user()->id)->latest()->get();
        }
        return view('backend.content.account.account', ['blances' => $blances]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function withdraws(Request $request)
    {
        $blances = Vencomment::where('type', 'Withdraw')->where('shop_id', Auth::guard('admin')->user()->id)->latest()->get();
        return view('backend.content.account.withdraw', ['blances' => $blances]);
    }
    public function withdrawviewslug($slug)
    {
        $blances = Vencomment::where('type', 'Withdraw')->where('status', $slug)->latest()->paginate(50);
        return view('backend.content.account.withdrawview', ['blances' => $blances]);
    }

    public function withdrawview(Request $request)
    {
        $blances = Vencomment::where('type', 'Withdraw')->latest()->paginate(1);
        return view('backend.content.account.withdrawview', ['blances' => $blances]);
    }

    public function withdrawedit($id)
    {
        $blances = Vencomment::where('id', $id)->first();
        return response()->json($blances);
    }

    public function withdrawupdate(Request $request, $id)
    {
        $blances = Vencomment::where('id', $id)->first();
        $blances->status = $request->status;
        $blances->update();
        return response()->json($blances);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function withdrawstore(Request $request)
    {
        $user = Admin::where('id', Auth::guard('admin')->user()->id)->first();
        if ($user->account_balance > $request->amount) {
            $blc = $user->account_balance;
            $deposit = new Vencomment();
            $deposit->payment_type = $request->payment_type;
            $deposit->account_number = $request->account_number;
            $deposit->additional_info = $request->additional_info;
            $deposit->shop_id = $user->id;
            $deposit->comment = 'You give a withdraw request for ' . $request->amount . 'TK';
            $deposit->status = 'Pending';
            $deposit->type = 'Withdraw';
            $deposit->amount = $request->amount;
            $deposit->blance = $blc - $request->amount;
            $success = $deposit->save();

            if ($success) {
                $user->account_balance = $user->account_balance - $request->amount;
                $user->update();
            } else {
                $deposit->delete();
            }
            return response()->json('success', 200);
        } else {
            return response()->json('error', 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Vencomment  $vencomment
     * @return \Illuminate\Http\Response
     */
    public function show(Vencomment $vencomment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Vencomment  $vencomment
     * @return \Illuminate\Http\Response
     */
    public function edit(Vencomment $vencomment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vencomment  $vencomment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Vencomment $vencomment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Vencomment  $vencomment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Vencomment $vencomment)
    {
        //
    }
}
