<?php

namespace App\Http\Controllers;

use App\Models\Resellerinvoice;
use Illuminate\Http\Request;
use DataTables;
use App\Models\User;
 use App\Models\Order;
use App\Models\Package;
use App\Models\Basicinfo;
use App\Models\Income;
use App\Models\Message;
use App\Models\Withdrew;
use App\Models\Chargededuct;
use Illuminate\Support\Facades\Auth;
use DB;
class ResellerinvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($status)
    {
        return view('backend.content.invoice.index', ['status' => $status]);
    }

public function incomehistory($id)
{
    $user = User::where('id', $id)->first();
    $incomes = Income::where('user_id', $id)->get();
    $chargededucts = Chargededuct::where('user_id', $id)->get();
    // $withdrews = Withdrew::where('user_id', $id)->get(); // if still needed

    return view('backend.content.withdrew.history', [
        'user' => $user,
        'incomes' => $incomes,
        'chargededucts' => $chargededucts,
        // 'withdrews' => $withdrews
    ]);
}

public function incomeHistoryOrders(Request $request, $id)
{
    $user = User::findOrFail($id);

    $query = Order::where('user_id', $user->id)
                  ->select([
                      'orderDate',
                      'invoiceID',
                      'subTotal',
                      'deliveryCharge',
                      'discountCharge',
                      'paymentAmount',
                      'profit',
                      'order_bonus',
                      'status',
                      'Payment'
                  ]);

    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereBetween('orderDate', [$request->from_date, $request->to_date]);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    return DataTables::of($query)
        ->editColumn('orderDate', function ($order) {
            return $order->orderDate 
                ? \Carbon\Carbon::parse($order->orderDate)->format('d M Y') 
                : '-';
        })
        ->rawColumns(['status']) // if you add HTML badges later
        ->make(true);
}
    public function autologin($id)
    {
        $credentials = User::where('id', $id)->first();
        if (Auth::guard('web')->check()) {
            return redirect()->back()->with('error', 'Already Login to an another account');
        } else {
            if (Auth::guard('web')->loginUsingId($credentials->id)) {
                return redirect('user/dashboard')->with('success', 'You have successfully use Auto login');
            }

            return redirect()->back()->with('error', 'Oppes! You have entered invalid credentials');
        }
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function invoicedata(Request $request, $status)
    {
        $search = $request['phone'];

        if ($status == 'all') {
            if ($search == '' || $search == null) {
                $invoices = Resellerinvoice::orderBy('resellerinvoices.id', 'DESC');
            } else {
                if (strlen($search) == '11') {
                    $user = User::where('email', 'LIKE', '%' . $search . '%')->get()->pluck('id');
                    if (count($user) > 0) {
                        $user = User::where('email', 'LIKE', '%' . $search . '%')->get()->pluck('id');
                    } else {
                        $ema = '88' . $search;
                        $user = User::where('email', $ema)->get()->pluck('id');
                    }
                } else {
                    $user = User::where('email', 'LIKE', '%' . $search . '%')->get()->pluck('id');
                }
                if (count($user) > 0) {
                    $invoices = Resellerinvoice::whereIn('user_id', $user)->orderBy('resellerinvoices.id', 'DESC');
                } else {
                    $invoices = [];
                }
            }
        } else {
            if ($search == '' || $search == null) {
                $invoices = Resellerinvoice::where('status', '=', $status)->orderBy('resellerinvoices.id', 'DESC');
            } else {
                if (strlen($search) == '11') {
                    $user = User::where('email', 'LIKE', '%' . $search . '%')->get()->pluck('id');
                    if (count($user) > 0) {
                        $user = User::where('email', 'LIKE', '%' . $search . '%')->get()->pluck('id');
                    } else {
                        $ema = '88' . $search;
                        $user = User::where('email', $ema)->get()->pluck('id');
                    }
                } else {
                    $user = User::where('email', 'LIKE', '%' . $search . '%')->get()->pluck('id');
                }
                if (count($user) > 0) {
                    $invoices = Resellerinvoice::whereIn('user_id', $user)->where('status', '=', $status)->orderBy('resellerinvoices.id', 'DESC');
                } else {
                    $invoices = [];
                }
            }
        }

        if (isset($search)) {
        } else {
            if ($request['startDate'] != '' && $request['endDate'] != '') {
                $invoices = $invoices->whereBetween('invoiceDate', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
            }
        }

        return Datatables::of($invoices)
            ->editColumn('user', function ($invoices) {
                if ($invoices->user_id) {
                    $ex = User::where('id', $invoices->user_id)->first();
                    if (isset($ex)) {
                        return '<a href="user/view-dashboard/' . $invoices->user_id . '" target="_blank">' . User::where('id', $invoices->user_id)->first()->name . '( <span style="color:#613EEA">' . User::where('id', $invoices->user_id)->first()->my_referral_code . ' </span>)</a>';
                    } else {
                        return 'User Name Not found';
                    }
                } else {
                    return 'user not founds';
                }
            })
            ->editColumn('package', function ($invoices) {
                if ($invoices->package_id) {
                    return Package::where('id', $invoices->package_id)->first()->package_name;
                } else {
                    return 'package not founds';
                }
            })
            ->editColumn('email', function ($invoices) {
                $ex = User::where('id', $invoices->user_id)->first();
                if (isset($ex)) {
                    return User::where('id', $invoices->user_id)->first()->email;;
                } else {
                    return 'User Email Not found';
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
                    } else if ($invoices->status == 'Unpaid') {
                        return '<button type="button" class="text-white btn btn-sm" style="background:#EB762A;border:1px solid #EB762A;">' . $invoices->status . '</button>';
                    } else if ($invoices->status == 'Cancel') {
                        return '<button type="button" class="text-white btn btn-sm" style="background:#F00;border:1px solid #F00;">' . $invoices->status . '</button>';
                    } else if ($invoices->status == 'Ban') {
                        return '<button type="button" class="text-white btn btn-sm" style="background:#613EEA;border:1px solid #613EEA;">' . $invoices->status . '</button>';
                    } else {
                        return '<button type="button" class="btn btn-primary btn-sm" >' . $invoices->status . '</button>';
                    }
                }
            )
            ->escapeColumns([])->make(true);
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

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Resellerinvoice  $resellerinvoice
     * @return \Illuminate\Http\Response
     */
    public function show(Resellerinvoice $resellerinvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Resellerinvoice  $resellerinvoice
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $invoices = Resellerinvoice::where('id', $id)->first();
        return response()->json($invoices, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Resellerinvoice  $resellerinvoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $invoice = Resellerinvoice::where('id', $id)->first();
        if ($request->resellerpackage == $invoice->resellerpackage) {
            if ($request->discount) {
                $invoice->payable_amount = $invoice->amount - $request->discount;
                $invoice->discount = $request->discount;
            }
        } else {
            $pack = Package::where('id', $request->resellerpackage)->first();
            $invoice->package_id = $request->resellerpackage;
            $invoice->amount = $pack->discount_price;
            $invoice->payable_amount = $pack->discount_price - $request->discount;
            $invoice->discount = $request->discount;
        }


        if ($request->status == 'Paid') {
            if ($invoice->status == 'Paid') {
                $invoice->paid_amount = $request->paidamount;
            } else {
                $user = User::where('id', $invoice->user_id)->first();

                $referuser = User::where('my_referral_code', $user->refer_by)->first();
                if ($referuser) {
                    $refbonus = $invoice->payable_amount * ($referuser->bonus_percent / 100);
                    $referuser->referal_bonus = $referuser->referal_bonus + $refbonus;
                    $referuser->account_balance = $referuser->account_balance + $refbonus;
                    $referuser->update();

                    $message = new Message();
                    $message->user_id = $referuser->id;
                    $message->message_for = 'Referral Bonus';
                    $message->message = 'You Get ' . $refbonus . ' TK As Your Referral Bonus';
                    $message->amount = $refbonus;
                    $message->date = date('Y-m-d');
                    $message->save();
                }

                $user->status = 'Active';
                $user->membership_status = 'Paid';
                $user->active_date = date('Y-m-d');
                $user->p_system = 'Manual';
                $user->update();
                $invoice->paymentDate = date('Y-m-d');
                $invoice->paid_amount = $request->paidamount;
            }
        } else {
            $invoice->paymentDate = '';
            if ($invoice->status == 'Paid') {
                if ($request->status == 'Unpaid' || $request->status == 'Cancel') {
                    $user = User::where('id', $invoice->user_id)->first();

                    $referuser = User::where('my_referral_code', $user->refer_by)->first();
                    if ($referuser) {
                        $refbonus = $invoice->payable_amount * ($referuser->bonus_percent / 100);
                        $referuser->referal_bonus = $referuser->referal_bonus - $refbonus;
                        $referuser->account_balance = $referuser->account_balance - $refbonus;
                        $referuser->update();
                        $message = new Message();
                        $message->user_id = $referuser->id;
                        $message->message_for = 'Referral Bonus';
                        $message->message = 'We Remove ' . $refbonus . ' TK From Your Referral Bonus';
                        $message->amount = -$refbonus;
                        $message->date = date('Y-m-d');
                        $message->save();
                    }

                    $user->status = 'Inactive';
                    $user->membership_status = 'Unpaid';
                    $user->active_date = date('Y-m-d');
                    $user->update();
                    $invoice->paymentDate = '';
                    $invoice->paid_amount = 0;
                } else {
                    $invoice->paid_amount = $request->paidamount;
                }
            }
        }



        if ($request->status == 'Ban') {
            $user = User::where('id', $invoice->user_id)->first();
            $user->status = 'Block';
            $user->update();
        } elseif ($request->status == 'Unpaid') {
            $user = User::where('id', $invoice->user_id)->first();
            $user->status = 'Inactive';
            $user->update();
        } elseif ($request->status == 'Cancel') {
            $user = User::where('id', $invoice->user_id)->first();
            $user->status = 'Inactive';
            $user->update();
        } else {
            $user = User::where('id', $invoice->user_id)->first();
            $user->status = 'Active';
            $user->update();
        }

        if ($request->bonus_percent) {
            $userss = User::where('id', $invoice->user_id)->first();
            $userss->bonus_percent = $request->bonus_percent;
            $invoice->bonus_percent = $request->bonus_percent;
            $userss->update();
        } else {
            $userss = User::where('id', $invoice->user_id)->first();
            $userss->bonus_percent = 0;
            $invoice->bonus_percent = 0;
            $userss->update();
        }

        $invoice->blocking_reason = $request->blocking_reason;
        $invoice->status = $request->status;
        $invoice->payment_type = $request->payment_type;
        $invoice->payment_id = $request->payment_id;
        $invoice->from_date = $request->from_date;
        $invoice->to_date = $request->to_date;
        if (isset($request->to_date)) {
            $userssex = User::where('id', $invoice->user_id)->first();
            $userssex->expire_date = $request->to_date;
            $userssex->update();
        } else {
            $userssex = User::where('id', $invoice->user_id)->first();
            $userssex->expire_date = '';
            $userssex->update();
        }
        $invoice->update();
        return response()->json($invoice, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Resellerinvoice  $resellerinvoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Resellerinvoice $resellerinvoice)
    {
        //
    }
}
