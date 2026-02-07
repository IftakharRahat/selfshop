<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Orderproduct;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Chargededuct;
use App\Models\Shopproduct;
use DB;
use App\Models\Admin;
use App\Models\User;
use App\Models\Resellerapi;
use Cart;
use Session;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class OrderController extends Controller
{

    public function orderstore(Request $request)
    {
        $product = Product::where('id', $request->productId)->first();
        $admin = Admin::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })->where('status', 'Active')->inRandomOrder()->first();

        $order = new Order();
        $order->store_id = 1;
        $order->invoiceID = $this->uniqueID();
        $order->subTotal = $request->subTotal;
        $order->deliveryCharge = $request->deliveryCharge;
        $order->customerNote = $request->customerNote;
        $order->orderDate = date('Y-m-d');
        $order->courier_id = 26;
        $order->customerNote = '';
        if (isset($admin)) {
            $order->admin_id = $admin->id;
        } else {
            $admin = Admin::findOrfail(1);
            $order->admin_id = $admin->id;
        }
        $result = $order->save();
        if ($result) {
            $customer = new Customer();
            $customer->order_id = $order->id;
            $customer->customerName = $request->userName;
            $customer->customerPhone = $request->userPhone;
            $customer->customerAddress = $request->userAddress;
            $customer->save();

            $orderProducts = new Orderproduct();
            $orderProducts->order_id = $order->id;
            $orderProducts->product_id = $product->id;
            $orderProducts->productCode = $product->ProductSku;
            $orderProducts->productName = $product->ProductName;
            $orderProducts->quantity = 1;
            $orderProducts->productPrice = $product->ProductResellerPrice;
            $orderProducts->save();

            $notification = new Comment();
            $notification->order_id = $order->id;
            $notification->comment =  $order->invoiceID . ' Order Has Been Created for ' . $admin->name;
            $notification->admin_id = $order->admin_id;
            $notification->save();
            Cart::destroy();
            Session::put('ordersubtotal', $request->subTotal);
            Session::put('orderdeliverycharge', $request->deliveryCharge);
            Session::put('order_id', $order->id);
            Session::put('invoiceID', $order->invoiceID);
            toastr()->info('Order Press Successfully', 'Complete', ["positionClass" => "toast-top-center"]);
            return redirect('order-received');
        } else {
            Customer::where('order_id', '=', $order->id)->delete();
            Orderproduct::where('order_id', '=', $order->id)->delete();
            Comment::where('order_id', '=', $order->id)->delete();
            Order::where('id', '=', $order->id)->delete();
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to press order';
        }



        return redirect('order-received');
    }

    public function pressorder(Request $request)
    {

        $shopproducts = Cart::content()->groupBy('weight');

        if (!Session::has('cart')) {
            return redirect('/empty-cart');
        } elseif (Cart::count() == 0) {
            return redirect('/empty-cart');
        } else {

            foreach ($shopproducts as $shopproduct) {
                $admin = Admin::whereHas('roles', function ($q) {
                    $q->where('name', 'Executive');
                })->where('add_by', 1)->where('status', 'Active')->inRandomOrder()->first();
                $order = new Order();
                $buy = 0;
                $bonus = 0;
                $sellprice = 0;
                foreach ($shopproduct as $product) {
                    $sellprice += $product->price * $product->qty;
                    $buy += Product::where('id', $product->id)->first()->ProductResellerPrice * $product->qty;
                    $bonus += Product::where('id', $product->id)->first()->reseller_bonus;
                }
                $shopamount = $sellprice;
                $order->profit = $shopamount - $buy;
                $order->order_bonus = $bonus;
                $order->user_id = Auth::id();
                $order->courier_id = 26;
                $order->store_id = $shopproduct[0]->weight;
                $order->invoiceID = $this->uniqueID();
                $order->subTotal = $request->subTotal;
                $order->deliveryCharge = $request->deliveryCharge;
                $order->customerNote = $request->customerNote;
                if ($request->blance_from == 'from_account') {
                    $order->paymentAmount = $request->deliveryCharge;
                    $order->payment_type_id = 5;
                }
                $order->orderDate = date('Y-m-d');
                if (isset($admin)) {
                    $order->admin_id = $admin->id;
                } else {
                    $admin = Admin::where('id', $shopproduct[0]->weight)->first();
                    $order->admin_id = 1;
                }
                $result = $order->save();

                if ($result) {
                    $customer = new Customer();
                    $customer->order_id = $order->id;
                    $customer->customerName = $request->customerName;
                    $customer->customerPhone = $request->customerPhone;
                    $customer->customerAddress = $request->customerAddress;
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

                    if ($request->blance_from == 'from_account') {
                        $accountuser = User::where('id', Auth::user()->id)->first();
                        $accountuser->account_balance = $accountuser->account_balance - $request->deliveryCharge;
                        $accountuser->update();
                        $chargededucts = new Chargededuct();
                        $chargededucts->user_id = Auth::user()->id;
                        $chargededucts->comment =  'You have charged ' . $request->deliveryCharge . ' TK for delivery charge.';
                        $chargededucts->amount = $request->deliveryCharge;
                        $chargededucts->status = 'Success';
                        $chargededucts->save();
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


            Cart::destroy();
            toastr()->info('Order Press Successfully', 'Complete', ["positionClass" => "toast-top-center"]);
            return redirect('order-received');
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

    public function updatepaymentmethood(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();
        $order->Payment = $request->payment_option;
        $order->update();
        Session::put('successfulor', 'successfulor');
        return redirect('order/complete');
    }

    public function getorder()
    {
        $from = date('Y-m-d' . ' 00:00:00', time()); //need a space after dates.
        $to = date('Y-m-d' . ' 24:60:60', time());


        $now = Carbon::now();
        $yesterday = Carbon::now()->subDays(5);

        $orders = DB::table('orders')->orderBy('id', 'DESC')->whereBetween('created_at', [$yesterday, $now])->latest()->take(200)->get();

        $orders->map(function ($order) {
            $order->products = DB::table('orderproducts')
                ->leftjoin('products', 'orderproducts.product_id', '=', 'products.id')
                ->where('orderproducts.order_id', $order->id)->select('products.*', 'orderproducts.*')->get();
            return $order;
        });

        $orders->map(function ($order) {
            $order->customers = DB::table('customers')->where('customers.order_id', $order->id)->select('customers.id', 'customers.order_id', 'customers.customerName', 'customers.customerPhone', 'customers.customerAddress')->get();
            return $order;
        });

        return response()->json($orders, 201);
    }

    public function getproduct(Request $request)
    {
        $valid = Resellerapi::where('user_id', base64_decode($request->user_uuid))->where('api_key', $request->api_key)->where('api_secret', $request->api_secret)->first();
        if (isset($valid)) {
            $products = Product::where('status', 'Active')->select('id', 'ProductName', 'ProductImage')->get();
            $response = [
                'status' => 'true',
                'message' => 'Authorized',
                'data' => $products,
            ];
            return $response;
        } else {
            $response = [
                'status' => 'false',
                'message' => 'Unauthorized',
                'data' => '',
            ];
            return $response;
        }
    }

    public function getmyproduct(Request $request)
    {
        $valid = Resellerapi::where('user_id', base64_decode($request->user_uuid))->where('api_key', $request->api_key)->where('api_secret', $request->api_secret)->first();
        if (isset($valid)) {
            $shops = Shopproduct::where('user_id', base64_decode($request->user_uuid))->get();
            if (count($shops) > 0) {
                foreach ($shops as $shop) {
                    $ex = Product::where('id', $shop->product_id)->first();
                    if ($ex) {
                        $products[] = $ex;
                    }
                }
            } else {
                $products = [];
            }
            $response = [
                'status' => 'true',
                'message' => 'Authorized',
                'data' => $products,
            ];
            return $response;
        } else {
            $response = [
                'status' => 'false',
                'message' => 'Unauthorized',
                'data' => '',
            ];
            return $response;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

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
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
