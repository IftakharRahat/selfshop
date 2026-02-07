<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Courier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use DB;
use DataTables;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function courieruserreport()
    {
        return view('admin.content.report.courieruserreport');
    }

    public function paymentreport()
    {
        return view('admin.content.report.paymentreport');
    }

    public function productreport()
    {
        return view('admin.content.report.productreport');
    }
    public function courierreport()
    {
        return view('admin.content.report.courierreport');
    }
    public function userreport()
    {
        return view('admin.content.report.userreport');
    }


    public function courieruserreportdata(Request $request)
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        if ($admin->hasRole('Shop')) {
            $orders  = DB::table('orders')
                ->select('orders.*', 'customers.customerName', 'customers.customerPhone', 'customers.customerAddress', 'couriers.courierName', 'cities.cityName', 'zones.zoneName', 'admins.name')
                ->join('customers', 'orders.id', '=', 'customers.order_id')
                ->leftJoin('couriers', 'orders.courier_id', '=', 'couriers.id')
                ->leftJoin('cities', 'orders.city_id', '=', 'cities.id')
                ->leftJoin('zones', 'orders.zone_id', '=', 'zones.id')
                ->leftJoin('admins', 'orders.admin_id', '=', 'admins.id');

            if ($request['startDate'] != '' && $request['endDate'] != '') {
                if ($request['orderStatus'] == 'Delivered') {
                    $orders = $orders->whereBetween('orders.deliveryDate', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
                } else if ($request['orderStatus'] == 'Return') {
                    $orders = $orders->whereBetween('orders.completeDate', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
                } else {
                    $orders = $orders->whereBetween('orders.orderDate', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
                }
            }

            if ($request['courierID'] != '') {
                $orders = $orders->where('orders.courier_id', '=', $request['courierID']);
            }
            if ($request['orderStatus'] != 'All') {
                $orders = $orders->where('orders.status', 'like', $request['orderStatus']);
            }
            $orders = $orders->where('orders.store_id', '=', $admin->id);

            $orders = $orders->latest()->get();
            $order['data'] = $orders->map(function ($order) {
                $products = DB::table('orderproducts')->select('orderproducts.*')->where('order_id', '=', $order->id)->get();
                $orderProducts = '';
                $wholesale = 0;
                foreach ($products as $product) {
                    $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br>';

                    $exp = Product::where('id', $product->product_id)->first();
                    if ($exp) {
                        $wholesale += $product->quantity * $exp->ProductWholesalePrice;
                    } else {
                        $wholesale += 0;
                    }
                }
                $order->products = rtrim($orderProducts, '<br>');
                $order->wholesale = $wholesale;
                return $order;
            });
            return json_encode($order);
        } else {

            $orders  = DB::table('orders')
                ->select('orders.*', 'customers.customerName', 'customers.customerPhone', 'customers.customerAddress', 'couriers.courierName', 'cities.cityName', 'zones.zoneName', 'admins.name')
                ->join('customers', 'orders.id', '=', 'customers.order_id')
                ->leftJoin('couriers', 'orders.courier_id', '=', 'couriers.id')
                ->leftJoin('cities', 'orders.city_id', '=', 'cities.id')
                ->leftJoin('zones', 'orders.zone_id', '=', 'zones.id')
                ->leftJoin('admins', 'orders.admin_id', '=', 'admins.id');

            if ($request['startDate'] != '' && $request['endDate'] != '') {
                if ($request['orderStatus'] == 'Delivered') {
                    $orders = $orders->whereBetween('orders.deliveryDate', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
                } else if ($request['orderStatus'] == 'Return') {
                    $orders = $orders->whereBetween('orders.completeDate', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
                } else {
                    $orders = $orders->whereBetween('orders.orderDate', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
                }
            }

            if ($request['courierID'] != '') {
                $orders = $orders->where('orders.courier_id', '=', $request['courierID']);
            }
            if ($request['orderStatus'] != 'All') {
                $orders = $orders->where('orders.status', 'like', $request['orderStatus']);
            }
            if ($request['shopID'] != '') {
                $orders = $orders->where('orders.store_id', '=', $request['shopID']);
            }
            $orders = $orders->latest()->get();
            $order['data'] = $orders->map(function ($order) {
                $products = DB::table('orderproducts')->select('orderproducts.*')->where('order_id', '=', $order->id)->get();
                $orderProducts = '';
                $wholesale = 0;
                foreach ($products as $product) {
                    $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br>';
                    $exp = Product::where('id', $product->product_id)->first();
                    if ($exp) {
                        $wholesale += $product->quantity * $exp->ProductWholesalePrice;
                    } else {
                        $wholesale += 0;
                    }
                }
                $order->products = rtrim($orderProducts, '<br>');
                $order->wholesale = $wholesale;
                return $order;
            });
            return json_encode($order);
        }
    }

    public function courierreportdata(Request $request)
    {
        $response = [];
        if ($request['courierID'] == '') {
            $couriers = Courier::all();
            foreach ($couriers as $courier) {
                $temp['courier'] = $courier->courierName;
                $temp['date'] = $request['startDate'] . ' to ' . $request['endDate'];
                $temp['all'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, '');
                $temp['pending'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Pending');
                $temp['canceled'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Canceled');
                $temp['confirmed'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Confirmed');
                $temp['invoiced'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Invoiced');
                $temp['ondelivery'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'On Delivery');
                $temp['delivered'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Delivered');
                $temp['paidAmount'] = $this->getDateCourierAmount($request['startDate'], $request['endDate'], $courier->id, 'Delivered');
                $temp['return'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Return');
                array_push($response, $temp);
            }
        } else {
            $courier = Courier::find($request['courierID']);
            $temp['courier'] = $courier->courierName;
            $temp['date'] = $request['startDate'] . ' to ' . $request['endDate'];
            $temp['all'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, '');
            $temp['pending'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Pending');
            $temp['canceled'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Canceled');
            $temp['confirmed'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Confirmed');
            $temp['invoiced'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Invoiced');
            $temp['ondelivery'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'On Delivery');
            $temp['delivered'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Delivered');
            $temp['paidAmount'] = $this->getDateCourierAmount($request['startDate'], $request['endDate'], $courier->id, 'Delivered');
            $temp['return'] = $this->getDateCourier($request['startDate'], $request['endDate'], $courier->id, 'Return');
            array_push($response, $temp);
        }
        $result['data'] = $response;
        return json_encode($result);
    }

    public function getDateCourier($startDate, $endDate, $courierID, $status)
    {
        $orders  = DB::table('orders')
            ->select('orders.*', 'couriers.courierName')
            ->leftJoin('couriers', 'orders.courier_id', '=', 'couriers.id');
        $orders = $orders->where('orders.courier_id', '=', $courierID);

        if ($startDate != '' && $endDate != '') {
            $orders = $orders->whereBetween('orders.orderDate', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        if (!empty($status)) {

            $orders = $orders->Where('orders.status', '=', $status);
        }
        return $orders->get()->count();
    }

    public function getDateCourierAmount($startDate, $endDate, $courierID, $status)
    {
        $orders  = DB::table('orders')
            ->select('orders.*', 'couriers.courierName')
            ->leftJoin('couriers', 'orders.courier_id', '=', 'couriers.id');
        $orders = $orders->where('orders.courier_id', '=', $courierID);

        if ($startDate != '' && $endDate != '') {
            $orders = $orders->whereBetween('orders.orderDate', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        if (!empty($status)) {

            $orders = $orders->Where('orders.status', '=', $status);
        }
        return $orders->get()->sum('subTotal');
    }



    public function userreportdata(Request $request)
    {
        $response = [];
        if ($request['userID'] == '') {
            $users = User::where('status', 'Active')->where('membership_status', 'Paid')->get();
            foreach ($users as $user) {
                $temp['name'] = $user->name;
                $temp['date'] = $request['startDate'] . ' to ' . $request['endDate'];
                $temp['all'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, '');
                $temp['pending'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Pending');
                $temp['canceled'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Canceled');
                $temp['confirmed'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Confirmed');
                $temp['invoiced'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Invoiced');
                $temp['ondelivery'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'On Delivery');
                $temp['delivered'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Delivered');
                $temp['paidAmount'] = $this->getDateUserAmount($request['startDate'], $request['endDate'], $user->id, 'Delivered');
                $temp['return'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Return');
                array_push($response, $temp);
            }
        } else {
            $user = User::find($request['userID']);
            $temp['name'] = $user->name;
            $temp['date'] = $request['startDate'] . ' to ' . $request['endDate'];
            $temp['all'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, '');
            $temp['pending'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Pending');
            $temp['canceled'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Canceled');
            $temp['confirmed'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Confirmed');
            $temp['invoiced'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Invoiced');
            $temp['ondelivery'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'On Delivery');
            $temp['delivered'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Delivered');
            $temp['paidAmount'] = $this->getDateUserAmount($request['startDate'], $request['endDate'], $user->id, 'Delivered');
            $temp['return'] = $this->getDateUser($request['startDate'], $request['endDate'], $user->id, 'Return');
            array_push($response, $temp);
        }
        $result['data'] = $response;
        return json_encode($result);
    }
    public function getDateUser($startDate, $endDate, $userID, $status)
    {
        $orders  = DB::table('orders')
            ->select('orders.*', 'couriers.courierName')
            ->leftJoin('couriers', 'orders.courier_id', 'couriers.id');
        $orders = $orders->where('orders.user_id',  $userID);

        if ($startDate != '' && $endDate != '') {
            $orders = $orders->whereBetween('orders.orderDate', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        if (!empty($status)) {

            $orders = $orders->Where('orders.status', $status);
        }
        return $orders->get()->count();
    }

    public function getDateUserAmount($startDate, $endDate, $userID, $status)
    {
        $orders  = DB::table('orders')
            ->select('orders.*', 'couriers.courierName')
            ->leftJoin('couriers', 'orders.courier_id',  'couriers.id');
        $orders = $orders->where('orders.user_id',  $userID);

        if ($startDate != '' && $endDate != '') {
            $orders = $orders->whereBetween('orders.orderDate', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        if (!empty($status)) {

            $orders = $orders->Where('orders.status', '=', $status);
        }
        return $ar = $orders->get()->sum('subTotal');
    }

    public function paymentreportdata(Request $request)
    {
        $orders = DB::table('paymentcompletes')
            ->join('payments', 'paymentcompletes.payment_id', '=', 'payments.id')
            ->join('admins', 'paymentcompletes.userID', '=', 'admins.id')
            ->join('paymenttypes', 'paymentcompletes.payment_type_id', '=', 'paymenttypes.id');

        if ($request['startDate'] != '' && $request['endDate'] != '') {
            $orders = $orders->whereBetween('paymentcompletes.date', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
        }
        if ($request['userID'] != '') {
            $orders = $orders->where('paymentcompletes.userID', '=', $request['userID']);
        }
        if ($request['paymentID'] != '') {
            $orders = $orders->where('paymentcompletes.payment_id', '=', $request['paymentID']);
        }
        if ($request['paymentTypeID'] != '') {
            $orders = $orders->where('paymentcompletes.payment_type_id', '=', $request['paymentTypeID']);
        }
        $orders = $orders->where('paymentcompletes.amount', '!=', 0);
        return DataTables::of($orders)->make();
    }


    public function productreportdata(Request $request)
    {

        $status  = $request->input('orderStatus');

        $orders = DB::table('orders')
            ->join('orderproducts', 'orders.id', '=', 'orderproducts.order_id')
            ->select('orders.status', 'orders.deliveryDate', 'orders.completeDate', 'orders.orderDate', 'orderproducts.*', DB::raw('COUNT(orderproducts.order_id) as total_amount'))
            ->groupBy('orderproducts.product_id');



        if ($status != 'All') {
            $orders  = $orders->where('orders.status', 'like', $status);
        }



        if ($request['startDate'] != '' && $request['endDate'] != '') {
            if ($request['orderStatus'] == 'Delivered') {
                $orders = $orders->whereBetween('orders.deliveryDate', [$request['startDate'], $request['endDate']]);
            } else if ($request['orderStatus'] == 'Return') {
                $orders = $orders->whereBetween('orders.completeDate', [$request['startDate'], $request['endDate']]);
            } else {

                $orders = $orders->whereBetween('orders.orderDate', [$request['startDate'], $request['endDate']]);
            }
        }



        if ($request['courierID'] != '') {
            $orders = $orders->where('orders.courier_id', '=', $request['courierID']);
        }

        return DataTables::of($orders)->make();
    }
}