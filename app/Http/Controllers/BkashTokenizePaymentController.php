<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Chargededuct;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\Resellerinvoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Karim007\LaravelBkashTokenize\Facade\BkashPaymentTokenize;
use Karim007\LaravelBkashTokenize\Facade\BkashRefundTokenize;
use Cart;
use Session;

class BkashTokenizePaymentController extends Controller
{
    public function index()
    {
        return view('bkashT::bkash-payment');
    }
    public function createPayment(Request $request)
    {

        session()->put('customerinfo', $request->data);
        session()->put('invoice_amount', $request->data['deliveryCharge']);
        $inv = uniqid();
        $request['intent'] = 'sale';
        $request['mode'] = '0011'; //0011 for checkout
        $request['payerReference'] = $inv;
        $request['currency'] = 'BDT';
        $request['amount'] = $request->data['deliveryCharge'];
        $request['merchantInvoiceNumber'] = $inv;
        $request['callbackURL'] = config("bkash.callbackURL");;

        $request_data_json = json_encode($request->all());

        $response =  BkashPaymentTokenize::cPayment($request_data_json);
        //$response =  BkashPaymentTokenize::cPayment($request_data_json,1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..

        //store paymentID and your account number for matching in callback request
        // dd($response) //if you are using sandbox and not submit info to bkash use it for 1 response
        return response()->json($response);
        if (isset($response['bkashURL'])) return redirect()->away($response['bkashURL']);
        else return redirect()->back()->with('error-alert2', $response['statusMessage']);
    }

    public function callBack(Request $request)
    {
        //callback request params
        // paymentID=your_payment_id&status=success&apiVersion=1.2.0-beta
        //using paymentID find the account number for sending params

        if ($request->status == 'success') {
            $response = BkashPaymentTokenize::executePayment($request->paymentID);

            //$response = BkashPaymentTokenize::executePayment($request->paymentID, 1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..
            if (!$response) { //if executePayment payment not found call queryPayment
                $response = BkashPaymentTokenize::queryPayment($request->paymentID);
                //$response = BkashPaymentTokenize::queryPayment($request->paymentID,1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..
            }

            if (isset($response['statusCode']) && $response['statusCode'] == "0000" && $response['transactionStatus'] == "Completed") {


                $infos = session()->get('invoiceinfo');

                if ($infos) {
                    $invoice = Resellerinvoice::where('invoiceID', $infos['invoiceID'])->first();

                    if ($response['transactionStatus'] == "Completed") {
                        if ($invoice->status == 'Paid') {
                            $invoice->paid_amount = $response['amount'];
                        } else {
                            $user = User::where('id', $invoice->user_id)->first();
                            $referuser = User::where('my_referral_code', $user->refer_by)->first();
                            $refbonus = 200;
                            $referuser->referal_bonus = $referuser->referal_bonus + $refbonus;
                            $referuser->account_balance = $referuser->account_balance + $refbonus;
                            $referuser->update();

                            $user->status = 'Active';
                            $user->membership_status = 'Paid';
                            $user->active_date = date('Y-m-d');
                            $user->p_system = 'Getway';
                            $user->update();
                            $invoice->paymentDate = date('Y-m-d');
                            $invoice->paid_amount = $response['amount'];

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
                    $invoice->payment_id = $response['trxID'];
                    $invoice->status = 'Paid';
                    $invoice->update();
                    session()->forget('invoiceinfo');
                    return redirect('user/dashboard');
                } else {
                    $shopproducts = Cart::content()->groupBy('weight');

                    $info = session()->get('customerinfo');
                    foreach ($shopproducts as $shopproduct) {
                        $admin = Admin::whereHas('roles', function ($q) {
                            $q->where('name', 'user');
                        })->where('add_by', $shopproduct[0]->weight)->where('status', 'Active')->inRandomOrder()->first();
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
                        $order->store_id = $shopproduct[0]->weight;
                        $order->invoiceID = $this->uniqueID();
                        $order->subTotal = $info['subTotal'];
                        $order->deliveryCharge = $info['deliveryCharge'];
                        if (isset($info['customerNote'])) {
                            $order->customerNote = $info['customerNote'];
                        }
                        $order->paymentAmount = $info['deliveryCharge'];
                        $order->payment_type_id = 1;
                        $order->trx_id = $response['trxID'];

                        $order->orderDate = date('Y-m-d');
                        if (isset($admin)) {
                            $order->admin_id = $admin->id;
                        } else {
                            $admin = Admin::where('id', $shopproduct[0]->weight)->first();
                            $order->admin_id = $shopproduct[0]->weight;
                        }
                        $result = $order->save();

                        if ($result) {
                            $customer = new Customer();
                            $customer->order_id = $order->id;
                            $customer->customerName = $info['customerName'];
                            $customer->customerPhone = $info['customerPhone'];
                            $customer->customerAddress = $info['customerAddress'];
                            $customer->save();
                            foreach ($shopproduct as $product) {
                                $orderProducts = new Orderproduct();
                                $orderProducts->order_id = $order->id;
                                $orderProducts->product_id = $product->id;
                                $orderProducts->productCode = $product->options['code'];
                                if ($product->options['color'] == 'undefined') {
                                } else {
                                    $orderProducts->color = $product->options['color'];
                                }

                                if ($product->options['size'] == 'undefined') {
                                } else {
                                    $orderProducts->size = $product->options['size'];
                                }

                                $orderProducts->productName = $product->name;
                                $orderProducts->quantity = $product->qty;
                                $orderProducts->productPrice = $product->price;
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
                            Orderproduct::where(
                                'order_id',
                                '=',
                                $order->id
                            )->delete();
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

                    session()->forget('customerinfo');
                    return redirect('order-received');
                }
            }
            return BkashPaymentTokenize::failure($response['statusMessage']);
        } else if ($request->status == 'cancel') {
            return BkashPaymentTokenize::cancel('Your payment is canceled');
        } else {
            return BkashPaymentTokenize::failure('Your transaction is failed');
        }
    }
    public function uniqueID()
    {
        $lastOrder = Order::latest('id')->first();
        if ($lastOrder) {
            $orderID = $lastOrder->id + 1;
        } else {
            $orderID = 1;
        }

        return 'SS00' . $orderID;
    }
    public function searchTnx($trxID)
    {
        //response
        return BkashPaymentTokenize::searchTransaction($trxID);
        //return BkashPaymentTokenize::searchTransaction($trxID,1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..
    }

    public function refund(Request $request)
    {
        $paymentID = 'Your payment id';
        $trxID = 'your transaction no';
        $amount = 5;
        $reason = 'this is test reason';
        $sku = 'abc';
        //response
        return BkashRefundTokenize::refund($paymentID, $trxID, $amount, $reason, $sku);
        //return BkashRefundTokenize::refund($paymentID,$trxID,$amount,$reason,$sku, 1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..
    }
    public function refundStatus(Request $request)
    {
        $paymentID = 'Your payment id';
        $trxID = 'your transaction no';
        return BkashRefundTokenize::refundStatus($paymentID, $trxID);
        //return BkashRefundTokenize::refundStatus($paymentID,$trxID, 1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..
    }

    public function invindex()
    {
        return view('bkashT::bkash-payment');
    }
    public function invcreatePayment(Request $request)
    {

        $inv = rand();
        $request['payerReference'] = $inv;
        $request['merchantInvoiceNumber'] = $request->invoiceID;
        $request['intent'] = 'sale';
        $request['currency'] = 'BDT';
        $request['mode'] = '0011';
        $request['amount'] = $request->amount;
        $request['callbackURL'] = config("bkash.callbackURL");

        $request_data_json = json_encode($request->all());

        $response =  BkashPaymentTokenize::cPayment($request_data_json);

        return response()->json($response);
        if (isset($response['bkashURL'])) return redirect()->away($response['bkashURL']);
        else return redirect()->back()->with('error-alert2', $response['statusMessage']);
    }


    public function invsearchTnx($trxID)
    {
        //response
        return BkashPaymentTokenize::searchTransaction($trxID);
        //return BkashPaymentTokenize::searchTransaction($trxID,1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..
    }
}