<?php

namespace App\Http\Controllers;

use App\Models\Withdrew;
use App\Models\User;
use App\Models\Paymenttype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use DataTables;
use App\Models\Message;
use App\Models\Comment;
use App\Models\Order;

class WithdrewController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($status)
    {
        return view('backend.content.withdrew.withdrew', ['status' => $status]);
    }

    public function withdrewdatas($status)
    {
        if ($status == 'All') {
            $invoices = Withdrew::orderBy('withdrews.id', 'DESC');
        } else {
            $invoices = Withdrew::where('status', '=', $status)->orderBy('withdrews.id', 'DESC');
        }
        return Datatables::of($invoices)
            ->editColumn('user', function ($invoices) {
                if ($invoices->user_id) {
                    return '<a href="../../resellerinvoice/user/view-dashboard/' . $invoices->user_id . '" target="_blank">' . User::where('id', $invoices->user_id)->first()->name . '(' . User::where('id', $invoices->user_id)->first()->my_referral_code . ')' . '</a><br> Date: ' . $invoices->created_at->format('Y-m-d') . '<br><a class="btn btn-success btn-sm" href="../../user/view-incomehistory/' . $invoices->user_id . '" target="_blank">See History</a>';
                } else {
                    return 'user not founds';
                }
            })
            ->addColumn('action', function ($invoices) {
                return '<a href="#" type="button" id="editFrdBtn" data-id="' . $invoices->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainFrd" ><i class="bi bi-pencil-square"></i></a>';
            })
            ->addColumn(
                'status',
                function ($invoices) {
                    if ($invoices->status == 'Paid') {
                        return '<button type="button" class="text-white btn btn-sm" style="background:#14BF7D;border:1px solid #14BF7D;">' . $invoices->status . '</button>';
                    } else if ($invoices->status == 'Pending') {
                        return '<button type="button" class="text-white btn btn-sm" style="background:#EB762A;border:1px solid #EB762A;">' . $invoices->status . '</button>';
                    } else if ($invoices->status == 'Cancel') {
                        return '<button type="button" class="text-white btn btn-sm" style="background:#F00;border:1px solid #F00;">' . $invoices->status . '</button>';
                    } else {
                        return '<button type="button" class="btn btn-primary btn-sm" >' . $invoices->status . '</button>';
                    }
                }
            )
            ->escapeColumns([])->make(true);
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

        $user = User::where('id', Auth::user()->id)->where('status', 'Active')->first();

        if (isset($user)) {
            if ($user->account_balance >= intval($request->withdrew_amount)) {
                $paymenttypes = Paymenttype::where('id', $request->paymenttype_id)->first();
                $withdrew = new Withdrew();
                $withdrew->user_id = Auth::user()->id;
                $withdrew->paymenttype_id = $request->paymenttype_id;
                $withdrew->paymenttype_name = $paymenttypes->paymentTypeName;
                $withdrew->to_account_number = $request->to_account_number;
                $withdrew->withdrew_amount = $request->withdrew_amount;
                $success = $withdrew->save();

                $user->account_balance = $user->account_balance - $request->withdrew_amount;
                $user->pending_cashout_balance = $user->pending_cashout_balance + $request->withdrew_amount;
                $user->update();


                $comment = new Comment();
                $comment->comment = 'You have sent a payment request via ' . $paymenttypes->paymentTypeName . ' Invoice ID: #IN00' . $withdrew->id;
                $comment->user_id = Auth::guard('web')->user()->id;
                $comment->status = 1;
                $comment->type = 'Withdraw';
                $comment->save();

                return response()->json($withdrew, 200);
            } else {
                return response()->json('lessblance', 200);
            }
        } else {
            return response()->json('lessblance', 200);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Withdrew  $withdrew
     * @return \Illuminate\Http\Response
     */
    public function withdrewdata()
    {
        $withdrew = Withdrew::get()->where('user_id', Auth::user()->id)->where('account_type', 0)->reverse();
        return Datatables::of($withdrew)
            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Withdrew  $withdrew
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $invoices = Withdrew::where('id', $id)->first();
        return response()->json($invoices, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Withdrew  $withdrew
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $withdrew = Withdrew::where('id', $id)->first();
        $withdrew->status = $request->status;
        $success = $withdrew->update();
        if ($success) {

            if ($request->status == 'Paid') {
                $user = User::where('id', $withdrew->user_id)->first();
                $user->pending_cashout_balance = $user->pending_cashout_balance - $withdrew->withdrew_amount;
                $user->cashout_balance = $user->cashout_balance + $withdrew->withdrew_amount;
                $comment = new Comment();
                $comment->comment = 'Request Accepted.Payment given successfully for invoice #IN00' . $withdrew->id;
                $comment->user_id = $withdrew->user_id;
                $comment->status = 1;
                $comment->type = 'Withdrawpaid';
                $comment->save();
                $user->update();
            } else if ($request->status == 'Cancel') {
                $user = User::where('id', $withdrew->user_id)->first();
                $user->account_balance = ($user->account_balance) + $withdrew->withdrew_amount;
                $user->pending_cashout_balance = $user->pending_cashout_balance - $withdrew->withdrew_amount;
                $comment = new Comment();
                $comment->comment = 'Payment request cancel for invoice #IN00' . $withdrew->id . ' and refound it your account.';
                $comment->user_id = $withdrew->user_id;
                $comment->status = 1;
                $comment->type = 'Withdrawcancel';
                $comment->save();
                $user->update();
            } else {
            }
        }
        return response()->json($withdrew, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Withdrew  $withdrew
     * @return \Illuminate\Http\Response
     */
    public function destroy(Withdrew $withdrew)
    {
        //
    }
}
