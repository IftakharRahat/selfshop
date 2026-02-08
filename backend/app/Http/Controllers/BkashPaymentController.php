<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Karim007\LaravelBkash\Facade\BkashPayment;
use Karim007\LaravelBkash\Facade\BkashRefund;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Orderproduct;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Message;
use App\Models\Chargededuct;
use App\Models\Resellerinvoice;
use DB;
use App\Models\Admin;
use App\Models\User;
use Cart;
use Session;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BkashPaymentController extends Controller
{
    public function index()
    {
        return view('bkash::bkash-payment');
    }

    public function getToken(Request $request)
    {
        session()->put('customerinfo', $request->data);
        session()->put('invoice_amount', $request->data['deliveryCharge']);
        return BkashPayment::getToken();
    }
    public function createPayment(Request $request)
    {
        $request['intent'] = 'sale';
        $request['currency'] = 'BDT';
        $request['amount'] = session()->get('invoice_amount') ?? 100;
        $request['merchantInvoiceNumber'] = rand();
        $request['callbackURL'] = config("bkash.callbackURL");;

        $request_data_json = json_encode($request->all());

        return BkashPayment::cPayment($request_data_json);
    }


    public function executePayment(Request $request)
    {
        $paymentID = $request->paymentID;
        return BkashPayment::executePayment($paymentID);
    }
    public function queryPayment(Request $request)
    {
        $paymentID = $request->payment_info['payment_id'];
        return BkashPayment::queryPayment($paymentID);
    }


    public function bkashSuccess(Request $request)
    {
        $pay_success = $request->payment_info['transactionStatus'];
        BkashPayment::bkashSuccess($pay_success);
        $shopproducts = Cart::content()->groupBy('shop_id');

        $info = session()->get('customerinfo');
        foreach ($shopproducts as $shopproduct) {
            $admin = Admin::whereHas('roles', function ($q) {
                $q->where('name', 'user');
            })->where('add_by', $shopproduct[0]->shop_id)->where('status', 'Active')->inRandomOrder()->first();
            $order = new Order();
            $buy = 0;
            $bonus = 0;
            foreach ($shopproduct as $product) {
                $buy += Product::where('id', $product->id)->first()->ProductResellerPrice * $product->qty;
                $bonus += Product::where('id', $product->id)->first()->reseller_bonus;
            }
            $order->profit = $info['subTotal'] - $buy;
            $order->order_bonus = $bonus;
            $order->user_id = Auth::id();
            $order->store_id = $shopproduct[0]->shop_id;
            $order->invoiceID = $this->uniqueID();
            $order->subTotal = $info['subTotal'];
            $order->deliveryCharge = $info['deliveryCharge'];
            if (isset($info['customerNote'])) {
                $order->customerNote = $info['customerNote'];
            }
            $order->paymentAmount = $info['deliveryCharge'];
            $order->payment_type_id = 1;
            $order->trx_id = $request->payment_info['trxID'];

            $order->orderDate = date('Y-m-d');
            if (isset($admin)) {
                $order->admin_id = $admin->id;
            } else {
                $admin = Admin::where('id', $shopproduct[0]->shop_id)->first();
                $order->admin_id = $shopproduct[0]->shop_id;
            }
            $result = $order->save();

            if ($result) {
                $customer = new Customer();
                $customer->order_id = $order->id;
                $customer->customerName = $info['customerName'];
                $customer->customerPhone = $info['customerPhone'];
                $customer->customerAddress = $info['customerAddress'];
                $customer->save();
                foreach ($shopproduct as $productn) {
                    $orderProducts = new Orderproduct();
                    $orderProducts->order_id = $order->id;
                    $orderProducts->product_id = $productn->id;
                    $orderProducts->productCode = $productn->options['code'];
                    if ($productn->options['color'] == 'undefined') {
                    } else {
                        $orderProducts->color = $productn->options['color'];
                    }

                    if ($productn->options['size'] == 'undefined') {
                    } else {
                        $orderProducts->size = $productn->options['size'];
                    }

                    $orderProducts->productName = $productn->name;
                    $orderProducts->quantity = $productn->qty;
                    $orderProducts->productPrice = $productn->price;
                    $orderProducts->save();
                }

                $notification = new Comment();
                $notification->order_id = $order->id;
                $notification->comment =  $order->invoiceID . ' Order Has Been Created for ' . $admin->name;
                $notification->admin_id = $order->admin_id;
                $notification->save();
                Session::put('order_id', $order->id);
                Session::put('invoiceID', $order->invoiceID);
            } else {
                Customer::where('order_id', '=', $order->id)->delete();
                Orderproduct::where('order_id', '=', $order->id)->delete();
                Comment::where('order_id', '=', $order->id)->delete();
                Order::where('id', '=', $order->id)->delete();
                $response['status'] = 'failed';
                $response['message'] = 'Unsuccessful to press order';
            }
        }

        $chargededucts = new Chargededuct();
        $chargededucts->user_id = Auth::user()->id;
        $chargededucts->comment =  'You have paid ' . $info['deliveryCharge'] . ' TK for delivery charge using Bkash.';
        $chargededucts->amount = $info['deliveryCharge'];
        $chargededucts->status = 'Success';
        $chargededucts->save();

        return redirect('order-received');
    }

    public function refundPage()
    {
        return BkashRefund::index();
    }

    public function uniqueID()
    {
        $lastOrder = Order::latest('id')->first();
        if ($lastOrder) {
            $orderID = $lastOrder->id + 1;
        } else {
            $orderID = 1;
        }

        return 'RR00' . $orderID;
    }



    public function refund(Request $request)
    {
        $this->validate($request, [
            'payment_id' => 'required',
            'amount' => 'required',
            'trx_id' => 'required',
            'sku' => 'required|max:255',
            'reason' => 'required|max:255'
        ]);

        $post_fields = [
            'paymentID' => $request->payment_id,
            'amount' => $request->amount,
            'trxID' => $request->trx_id,
            'sku' => $request->sku,
            'reason' => $request->reason,
        ];
        return BkashRefund::refund($post_fields);
    }

    // inv bkash

    public function invindex()
    {
        return view('bkash::bkash-payment');
    }

    public function invgetToken(Request $request)
    {
        session()->put('invoiceinfo', $request->data);
        session()->put('invoice_amount', $request->data['amount']);
        return BkashPayment::getToken();
    }
    public function invcreatePayment(Request $request)
    {
        $request['intent'] = 'sale';
        $request['currency'] = 'BDT';
        $request['amount'] = session()->get('invoice_amount') ?? 100;
        $request['merchantInvoiceNumber'] = rand();
        $request['callbackURL'] = config("bkash.callbackURL");;

        $request_data_json = json_encode($request->all());

        return BkashPayment::cPayment($request_data_json);
    }


    public function invexecutePayment(Request $request)
    {
        $paymentID = $request->paymentID;
        return BkashPayment::executePayment($paymentID);
    }
    public function invqueryPayment(Request $request)
    {
        $paymentID = $request->payment_info['payment_id'];
        return BkashPayment::queryPayment($paymentID);
    }


    public function invbkashSuccess(Request $request)
    {
        $pay_success = $request->payment_info['transactionStatus'];
        BkashPayment::bkashSuccess($pay_success);
        $info = session()->get('invoiceinfo');

        $invoice = Resellerinvoice::where('id', $info['invoiceID'])->first();

        if ($request->payment_info['transactionStatus'] == 'Completed') {
            if ($invoice->status == 'Paid') {
                $invoice->paid_amount = $request->payment_info['amount'];
            } else {
                $user = User::where('id', $invoice->user_id)->first();
                $referuser = User::where('my_referral_code', $user->refer_by)->first();
                $refbonus = 200;
                $referuser->referal_bonus = $referuser->referal_bonus + $refbonus;
                $referuser->account_balance = $referuser->account_balance + $refbonus;
                $referuser->update();

                $user->status = 'Active';
                $user->membership_status = 'Paid';
                $user->update();
                $invoice->paymentDate = date('Y-m-d');
                $invoice->paid_amount = $request->payment_info['amount'];

                $message = new Message();
                $message->user_id = $referuser->id;
                $message->message_for = 'Referral Bonus';
                $message->message = 'You Get ' . $refbonus . ' TK As Your Referral Bonus';
                $message->amount = $refbonus;
                $message->date = date('Y-m-d');
                $message->save();
            }
        }

        $invoice->payment_type = 'Bkash';
        $invoice->payment_id = $request->payment_info['trxID'];
        $invoice->status = 'Paid';
        $invoice->update();

        return redirect('user/dashboard');
    }

    public function invrefundPage()
    {
        return BkashRefund::index();
    }


    public function invrefund(Request $request)
    {
        $this->validate($request, [
            'payment_id' => 'required',
            'amount' => 'required',
            'trx_id' => 'required',
            'sku' => 'required|max:255',
            'reason' => 'required|max:255'
        ]);

        $post_fields = [
            'paymentID' => $request->payment_id,
            'amount' => $request->amount,
            'trxID' => $request->trx_id,
            'sku' => $request->sku,
            'reason' => $request->reason,
        ];
        return BkashRefund::refund($post_fields);
    }
}
