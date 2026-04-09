<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\City;
use App\Models\Comment;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Payment;
use App\Models\Paymentcomplete;
use App\Models\Paymenttype;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Admin;
use App\Models\Zone;
use App\Models\User;
use App\Models\Income;
use App\Models\Vencomment;
use App\Notifications\AdminBroadcastNotification;
use App\Services\SteadfastOrderStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use DataTables;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Session;

class OrderController extends Controller
{
    private function findOrderIncomeRecord(Order $order): ?Income
    {
        $query = Income::query()->where('from', 'Order');

        $query->where(function ($q) use ($order) {
            if (Schema::hasColumn('incomes', 'order_id')) {
                $q->where('order_id', $order->id);
            }

            if (Schema::hasColumn('incomes', 'invoice_code')) {
                $q->orWhere('invoice_code', (string) $order->invoiceID);
            }

            // Legacy fallback where invoice_id may store order primary key.
            $q->orWhere('invoice_id', (int) $order->id);
        });

        return $query->latest('id')->first();
    }

    private function markOrderIncomeCanceled(Order $order): void
    {
        $income = $this->findOrderIncomeRecord($order);
        if (!$income) {
            return;
        }

        $income->message = 'Opps ! We deduct ' . $order->profit . ' TK for cancel Order : ' . $order->invoiceID;
        $income->status = 'Canceled';
        $income->save();
    }

    private function recordOrderIncomePaid(Order $order): void
    {
        $income = $this->findOrderIncomeRecord($order) ?? new Income();
        $income->from = 'Order';
        $income->invoice_id = (int) $order->id;
        $income->message = 'Congratulations ! you get ' . $order->profit . ' TK from Order : ' . $order->invoiceID;
        $income->amount = $order->profit;
        $income->user_id = $order->user_id;
        $income->status = 'Paid';

        if (Schema::hasColumn('incomes', 'order_id')) {
            $income->order_id = $order->id;
        }

        if (Schema::hasColumn('incomes', 'invoice_code')) {
            $income->invoice_code = (string) $order->invoiceID;
        }

        $income->save();
    }

    private function configureOrderProductsQuery($query): void
    {
        $orderProductColumns = ['id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id'];
        if (Schema::hasColumn('orderproducts', 'fulfillment_status')) {
            $orderProductColumns[] = 'fulfillment_status';
        }
        if (Schema::hasColumn('orderproducts', 'tracking_number')) {
            $orderProductColumns[] = 'tracking_number';
        }
        if (Schema::hasColumn('orderproducts', 'shipped_at')) {
            $orderProductColumns[] = 'shipped_at';
        }

        $query->select($orderProductColumns);

        if (!Schema::hasTable('products')) {
            return;
        }

        $productColumns = ['id', 'ProductName'];
        $hasVendorSupport = Schema::hasColumn('products', 'vendor_id');
        if ($hasVendorSupport) {
            $productColumns[] = 'vendor_id';
        }

        if ($hasVendorSupport && Schema::hasTable('vendors')) {
            $query->with('product:' . implode(',', $productColumns), 'product.vendor:id,company_name');
            return;
        }

        $query->with('product:' . implode(',', $productColumns));
    }

    public function fraudcheck(Request $request)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://bdcourier.com/api/courier-check?phone=' . $request->number,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . 'DZNLWV0CZN3gxiYi3JshezgN398M0IIkBWXLPyvrLWplXc0bHLmhWcQMSzI0'
            ),
        ));

        $response = curl_exec($curl);
        $data = json_decode($response);

        return view('auth.fraud', ['response' => $data]);
    }

    public function assigncourier(Request $request)
    {

        $courier_id = $request['courier_id'];
        $courier = Courier::find($courier_id);
        $ids = $request['ids'];
        if ($ids) {
            foreach ($ids as $id) {
                $orders = DB::table('orders')
                    ->select('orders.*', 'customers.customerName', 'customers.customerPhone', 'customers.customerAddress', 'couriers.courierName', 'cities.cityName', 'zones.zoneName', 'admins.name',  'paymenttypes.paymentTypeName', 'payments.paymentNumber')
                    ->leftJoin('customers', 'orders.id', '=', 'customers.order_id')
                    ->leftJoin('couriers', 'orders.courier_id', '=', 'couriers.id')
                    ->leftJoin('paymenttypes', 'orders.payment_type_id', '=', 'paymenttypes.id')
                    ->leftJoin('payments', 'orders.payment_id', '=', 'payments.id')
                    ->leftJoin('cities', 'orders.city_id', '=', 'cities.id')
                    ->leftJoin('zones', 'orders.zone_id', '=', 'zones.id')
                    ->leftJoin('admins', 'orders.admin_id', '=', 'admins.id')
                    ->where('orders.id', '=', $id)->get()->first();
                $products = DB::table('orderproducts')->where('order_id', '=', $id)->get();
                $orders->products = $products;
                $orders->id = $id;

                if ($courier_id == 26) {
                    $api_key = env('STEADFAST_API_KEY');
                    $secret_key = env('STEADFAST_SECRET_KEY');
                    $ress = Http::withHeaders([
                        'Api-Key' => $api_key,
                        'Secret-Key' => $secret_key,
                        'Content-Type' => 'application/json'

                    ])->post('https://portal.packzy.com/api/v1/create_order', [
                        'invoice' => $orders->invoiceID,
                        'recipient_name' => $orders->customerName,
                        'recipient_address' => $orders->customerAddress,
                        'recipient_phone' => $orders->customerPhone,
                        'cod_amount' => $orders->subTotal,
                        'note' => $orders->customerNote,
                    ]);


                    $res = json_decode($ress->getBody()->getContents());


                    if (isset($res->consignment)) {
                        if ($res->consignment->status == 'in_review') {
                            $order = Order::find($id);
                            $order->status = 'Ontheway';
                            $order->tracking_number = $res->consignment->tracking_code;
                            $order->trackingLink = 'https://steadfast.com.bd/t' . '/' . $res->consignment->tracking_code;
                            $order->update();
                            $comment = new Comment();
                            $comment->order_id = $id;
                            $comment->comment = Auth::guard('admin')->user()->name . ' Successfully Send To #SS00' . $id . ' Order to ' . $courier->courierName;
                            $comment->admin_id = Auth::guard('admin')->user()->id;
                            $comment->save();

                            // Notify vendor(s) about the tracking ID
                            try {
                                $vendorIds = Orderproduct::where('order_id', $id)
                                    ->join('products', 'orderproducts.product_id', '=', 'products.id')
                                    ->whereNotNull('products.vendor_id')
                                    ->distinct()
                                    ->pluck('products.vendor_id');

                                if ($vendorIds->isNotEmpty()) {
                                    $notificationService = app(\App\Services\VendorAdminNotificationService::class);
                                    foreach ($vendorIds as $vendorId) {
                                        $notificationService->notifyVendorById(
                                            (int) $vendorId,
                                            'Courier Assigned - Tracking ID: ' . $res->consignment->tracking_code,
                                            'Order #SS00' . $id . ' has been assigned to ' . $courier->courierName . '. Tracking ID: ' . $res->consignment->tracking_code,
                                            'info',
                                            [
                                                'order_id' => $id,
                                                'tracking_number' => $res->consignment->tracking_code,
                                                'tracking_link' => 'https://steadfast.com.bd/t/' . $res->consignment->tracking_code,
                                            ]
                                        );
                                    }
                                }

                                // Real-time push notification for courier assignment
                                $pushService = app(\App\Services\PushNotificationService::class);
                                $pushService->onCourierAssigned($order, $courier->courierName, $res->consignment->tracking_code);
                            } catch (\Throwable $e) {
                                \Log::warning('Failed to notify about tracking ID', [
                                    'order_id' => $id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        } else {
                            $response['status'] = 'failed';
                            $response['message'] = 'This courier do not have permission for auto entry';
                        }
                    } else {
                        $response['status'] = 'failed';
                        $response['message'] = 'This courier do not have permission for auto entry';
                    }
                } else {
                    $response['status'] = 'failed';
                    $response['message'] = 'This courier do not have permission for auto entry';
                }
            }
            $response['status'] = 'success';
            $response['message'] = 'Successfully Assign Courier to this Order';
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Assign Courier to this Order';
        }
        return json_encode($response);
    }

    public function makeinvoicess($id)
    {
        $orders = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])->where('invoiceID', $id)->first();
        if ($orders) {
            $meta = app(SteadfastOrderStatusService::class)->syncOrderStatus($orders, false);
            $orders->setAttribute('customer_status', $meta['customer_status']);
            $orders->setAttribute('steadfast_status', $meta['steadfast_status']);
        }
        return view('webview.invoice', ['orders' => $orders]);
    }

    public function vieworder($id)
    {
        $orders = Order::with(['customers', 'orderproducts.product', 'couriers', 'cities', 'zones', 'admins'])->where('id', $id)->first();
        if ($orders) {
            $meta = app(SteadfastOrderStatusService::class)->syncOrderStatus($orders, false);
            $orders->setAttribute('customer_status', $meta['customer_status']);
            $orders->setAttribute('steadfast_status', $meta['steadfast_status']);
        }
        return view('admin.content.order.view', ['orders' => $orders]);
    }


    public function orderByproductindex()
    {
        return view('admin.content.order.productfindbyorder');
    }

    public function findByproduct(Request $request)
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        $order_ids = Orderproduct::where('productName', 'LIKE', "%{$request->product_name}%")->select('order_id')->get();

        if ($request->date) {
            if ($admin->hasRole('superadmin') || $admin->hasRole('admin') || $admin->hasRole('manager')) {
                if (isset($request->user_id)) {
                    foreach ($order_ids as $order_id) {
                        $orders[] =  Order::with([
                            'orderproducts' => function ($query) {
                                $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id');
                            },
                            'admins' => function ($query) {
                                $query->select('id', 'name');
                            },
                            'couriers' => function ($query) {
                                $query->select('id', 'courierName');
                            },
                            'products' => function ($query) {
                                $query->select('id', 'ProductName', 'ProductResellerPrice');
                            },
                            'comments' => function ($query) {
                                $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                            },
                            'cities' => function ($query) {
                                $query->select('id', 'courier_id', 'cityName');
                            },
                            'zones' => function ($query) {
                                $query->select('id', 'courier_id', 'city_id', 'zoneName');
                            }
                        ])->where('orders.id', $order_id->order_id)
                            ->where('orders.orderDate', $request->date)
                            ->where('admin_id', $request->user_id)
                            ->where('status', $request->status)
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')->first();
                    }
                } else {
                    foreach ($order_ids as $order_id) {
                        $orders[] =  Order::with([
                            'orderproducts' => function ($query) {
                                $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id');
                            },
                            'admins' => function ($query) {
                                $query->select('id', 'name');
                            },
                            'couriers' => function ($query) {
                                $query->select('id', 'courierName');
                            },
                            'products' => function ($query) {
                                $query->select('id', 'ProductName', 'ProductResellerPrice');
                            },
                            'comments' => function ($query) {
                                $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                            },
                            'cities' => function ($query) {
                                $query->select('id', 'courier_id', 'cityName');
                            },
                            'zones' => function ($query) {
                                $query->select('id', 'courier_id', 'city_id', 'zoneName');
                            }
                        ])->where('orders.id', $order_id->order_id)
                            ->where('orders.orderDate', $request->date)
                            ->where('status', $request->status)
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')->first();
                    }
                }
            } else {
                foreach ($order_ids as $order_id) {
                    $orders[] =  Order::with(
                        [
                            'orderproducts' => function ($query) {
                                $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id');
                            },
                            'admins' => function ($query) {
                                $query->select('id', 'name');
                            },
                            'couriers' => function ($query) {
                                $query->select('id', 'courierName');
                            },
                            'products' => function ($query) {
                                $query->select('id', 'ProductName', 'ProductResellerPrice');
                            },
                            'comments' => function ($query) {
                                $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                            },
                            'cities' => function ($query) {
                                $query->select('id', 'courier_id', 'cityName');
                            },
                            'zones' => function ($query) {
                                $query->select('id', 'courier_id', 'city_id', 'zoneName');
                            }
                        ]
                    )
                        ->where('orders.id', $order_id->order_id)
                        ->where('orders.orderDate', $request->date)
                        ->where('status', $request->status)
                        ->where('admin_id', Auth::guard('admin')->user()->id)
                        ->join('customers', 'customers.order_id', '=', 'orders.id')
                        ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')->first();
                }
            }
            return view('admin.content.order.productbyorder', ['orders' => $orders]);
        } else {
            if ($admin->hasRole('superadmin') || $admin->hasRole('admin') || $admin->hasRole('manager')) {
                if (isset($request->user_id)) {
                    foreach ($order_ids as $order_id) {
                        $orders[] =  Order::with([
                            'orderproducts' => function ($query) {
                                $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id');
                            },
                            'admins' => function ($query) {
                                $query->select('id', 'name');
                            },
                            'couriers' => function ($query) {
                                $query->select('id', 'courierName');
                            },
                            'products' => function ($query) {
                                $query->select('id', 'ProductName', 'ProductResellerPrice');
                            },
                            'comments' => function ($query) {
                                $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                            },
                            'cities' => function ($query) {
                                $query->select('id', 'courier_id', 'cityName');
                            },
                            'zones' => function ($query) {
                                $query->select('id', 'courier_id', 'city_id', 'zoneName');
                            }
                        ])->where('orders.id', $order_id->order_id)
                            ->where('status', $request->status)
                            ->where('admin_id', $request->user_id)
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')->first();
                    }
                } else {
                    foreach ($order_ids as $order_id) {
                        $orders[] =  Order::with([
                            'orderproducts' => function ($query) {
                                $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id');
                            },
                            'admins' => function ($query) {
                                $query->select('id', 'name');
                            },
                            'couriers' => function ($query) {
                                $query->select('id', 'courierName');
                            },
                            'products' => function ($query) {
                                $query->select('id', 'ProductName', 'ProductResellerPrice');
                            },
                            'comments' => function ($query) {
                                $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                            },
                            'cities' => function ($query) {
                                $query->select('id', 'courier_id', 'cityName');
                            },
                            'zones' => function ($query) {
                                $query->select('id', 'courier_id', 'city_id', 'zoneName');
                            }
                        ])->where('orders.id', $order_id->order_id)
                            ->where('status', $request->status)
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')->first();
                    }
                }
            } else {
                foreach ($order_ids as $order_id) {
                    $orders[] =  Order::with(
                        [
                            'orderproducts' => function ($query) {
                                $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id');
                            },
                            'admins' => function ($query) {
                                $query->select('id', 'name');
                            },
                            'couriers' => function ($query) {
                                $query->select('id', 'courierName');
                            },
                            'products' => function ($query) {
                                $query->select('id', 'ProductName', 'ProductResellerPrice');
                            },
                            'comments' => function ($query) {
                                $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                            },
                            'cities' => function ($query) {
                                $query->select('id', 'courier_id', 'cityName');
                            },
                            'zones' => function ($query) {
                                $query->select('id', 'courier_id', 'city_id', 'zoneName');
                            }
                        ]
                    )
                        ->where('orders.id', $order_id->order_id)
                        ->where('status', $request->status)
                        ->where('admin_id', Auth::guard('admin')->user()->id)
                        ->join('customers', 'customers.order_id', '=', 'orders.id')
                        ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')->first();
                }
            }
            return view('admin.content.order.productbyorder', ['orders' => $orders]);
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        $status = "";
        return view('admin.content.order.order', ['admin' => $admin, 'status' => $status]);
    }

    public function complain()
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        $status = "orderall";
        return view('admin.content.order.complain', ['admin' => $admin, 'status' => $status]);
    }

    public function userorder()
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        $status = "userorderall";
        return view('admin.content.order.userorder', ['admin' => $admin, 'status' => $status]);
    }

    public function ordersByStatus($status)
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        $status = $status;
        if ($status == 'Ontheway') {
            return view('admin.content.order.invoiced', ['admin' => $admin, 'status' => $status]);
        }
        if ($status == 'Delivered' || $status == 'Return') {
            return view('admin.content.order.delivered', ['admin' => $admin, 'status' => $status]);
        } else {
            return view('admin.content.order.order', ['admin' => $admin, 'status' => $status]);
        }
    }

    public function createorder()
    {
        $uniqueId = $this->uniqueID();
        $couriers = Courier::all();
        return view('admin.content.order.createorder', ['couriers' => $couriers, 'uniqueId' => $uniqueId]);
    }

    public function downloadinfo()
    {
        return view('admin.content.order.download');
    }


    public function storeorder(Request $request)
    {
        $order = new Order();
        $order->invoiceID = $this->uniqueID();
        $order->store_id = $request['data']['storeID'];
        $order->subTotal = $request['data']['total'];
        $order->deliveryCharge = $request['data']['deliveryCharge'];
        $order->discountCharge = $request['data']['discountCharge'];
        $order->payment_type_id = $request['data']['paymentTypeID'];
        $order->payment_id = $request['data']['paymentID'];
        $order->paymentAmount = $request['data']['paymentAmount'];
        $order->paymentAgentNumber = $request['data']['paymentAgentNumber'];
        $order->orderDate = $request['data']['orderDate'];
        $order->courier_id = $request['data']['courierID'];
        $order->city_id = $request['data']['cityID'];
        $order->zone_id = $request['data']['zoneID'];
        $products = $request['data']['products'];

        $buy = 0;
        $bonus = 0;
        foreach ($products as $product) {
            $buy += Product::where('id', $product['productID'])->first()->ProductResellerPrice * $product['productQuantity'];
            $bonus += Product::where('id', $product['productID'])->first()->reseller_bonus;
        }
        $order->profit = $request['data']['total'] - $buy;
        $order->order_bonus = $bonus;

        $order->admin_id = Auth::guard('admin')->user()->id;
        $result = $order->save();
        if ($result) {
            $customer = new Customer();
            $customer->order_id = $order->id;
            $customer->customerName = $request['data']['customerName'];
            $customer->customerPhone = $request['data']['customerPhone'];
            $customer->customerAddress = $request['data']['customerAddress'];
            $customer->save();
            $vendorIds = [];
            foreach ($products as $product) {
                $orderProducts = new Orderproduct();
                $orderProducts->order_id = $order->id;
                $orderProducts->product_id = $product['productID'];
                $orderProducts->productCode = $product['productCode'];
                $orderProducts->color = $product['productColor'];
                $orderProducts->size = $product['productSize'];
                $orderProducts->productName = $product['productName'];
                $orderProducts->quantity = $product['productQuantity'];
                $orderProducts->productPrice = $product['productPrice'];
                $orderProducts->save();

                // Track vendor IDs for supplier notification
                $vendorId = Product::where('id', $product['productID'])->value('vendor_id');
                if ($vendorId) {
                    $vendorIds[(int) $vendorId] = true;
                }
            }

            // Decrement product stock
            app(\App\Services\StockService::class)->decrementForOrder($order->id);

            // Send SMS + Email to suppliers
            if (!empty($vendorIds)) {
                try {
                    $supplierNotification = app(\App\Services\SupplierOrderNotificationService::class);
                    $supplierNotification->notify($order, array_keys($vendorIds), $request['data']['customerName'] ?? null);
                } catch (\Throwable $e) {
                    \Log::warning('Supplier SMS/Email notification failed (admin order)', ['error' => $e->getMessage()]);
                }
            }

            $notification = new Comment();
            $notification->order_id = $order->id;
            $notification->comment = '#SS00' . $order->id . ' Order Has Been Created by ' . Auth::guard('admin')->user()->name;
            $notification->admin_id = Auth::guard('admin')->user()->id;
            $notification->save();

            if ($request['data']['paymentID'] != '' && $request['data']['paymentTypeID'] != '') {
                $paymentComplete = new Paymentcomplete();
                $paymentComplete->order_id = $order->id;
                $paymentComplete->payment_type_id = $request['data']['paymentTypeID'];
                $paymentComplete->payment_id = $request['data']['paymentID'];
                $paymentComplete->trid = $request['data']['paymentAgentNumber'];
                $paymentComplete->amount = $request['data']['paymentAmount'];
                $paymentComplete->date = date('Y-m-d');
                $paymentComplete->userID = Auth::guard('admin')->user()->id;
                $paymentComplete->save();
            }
            $response['status'] = 'success';
            $response['message'] = 'Successfully Add Order';
        } else {
            Customer::where('order_id', '=', $order->id)->delete();
            Orderproduct::where('order_id', '=', $order->id)->delete();
            Comment::where('order_id', '=', $order->id)->delete();
            Order::where('id', '=', $order->id)->delete();
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Add Order';
        }
        return json_encode($response);
        die();
    }

    //user all ordeer

    public function userorderall(Request $request)
    {

        $columns = $request->input('columns');
        $status = $request->input('status');

        $orders =  Order::with(
            [
                'orderproducts' => function ($query) {
                    $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size', 'product_id');
                },
                'admins' => function ($query) {
                    $query->select('id', 'name');
                },
                'users' => function ($query) {
                    $query->select('id', 'name');
                },
                'couriers' => function ($query) {
                    $query->select('id', 'courierName');
                },
                'products' => function ($query) {
                    $query->select('id', 'productName', 'productPrice');
                },
                'comments' => function ($query) {
                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                },
                'cities' => function ($query) {
                    $query->select('id', 'courier_id', 'cityName');
                },
                'zones' => function ($query) {
                    $query->select('id', 'courier_id', 'city_id', 'zoneName');
                }
            ]
        )->where('admin_id', Auth::guard('admin')->user()->id)
            ->join('customers', 'customers.order_id', '=', 'orders.id')
            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');

        if ($columns[1]['search']['value']) {
            $orders = $orders->Where('orders.invoiceID', 'like', "%{$columns[1]['search']['value']}%")
                ->orWhere('orders.web_ID', 'like', "%{$columns[1]['search']['value']}%");
        }
        if ($columns[2]['search']['value']) {
            $orders = $orders->Where('customers.customerPhone', 'like', "%{$columns[2]['search']['value']}%");
        }
        if ($columns[5]['search']['value']) {
            $orders = $orders->Where('orders.courier_id', '=', $columns[5]['search']['value']);
        }
        if ($columns[6]['search']['value']) {
            if ($status == 'Delivered') {
                $orders = $orders->Where('orders.deliveryDate', 'like', "%{$columns[6]['search']['value']}%");
            } elseif ($status == 'Paid' || $status == 'Return' || $status == 'Lost') {
                $orders = $orders->Where('orders.completeDate', 'like', "%{$columns[6]['search']['value']}%");
            } else {
                $orders = $orders->Where('orders.orderDate', 'like', "%{$columns[6]['search']['value']}%");
            }
        }

        if (Auth::user()->role == 0) {
            if ($columns[8]['search']['value']) {
                $orders = $orders->Where('orders.memo', '=', $columns[8]['search']['value']);
            }
        } else {
            if ($columns[8]['search']['value']) {
                $orders = $orders->Where('orders.user_id', '=', $columns[8]['search']['value']);
            }
        }

        return Datatables::of($orders->orderBy('orders.id', 'DESC'))
            ->addColumn('customerInfo', function ($orders) {
                return $orders->customerName . '<br>' . $orders->customerPhone . '<br>' . $orders->customerAddress . '<br>' . $orders->entry_complete;
            })
            ->addColumn('invoice', function ($orders) {
                $ago = $orders->created_at ? $orders->created_at->diffForHumans() : '';
                return '<a href="https://localhost/resellbd/admin_order/invoice-view/' . $orders->invoiceID . '" target="_blank"> ' . $orders->invoiceID . '<a><br>' . $orders->web_ID . '<br>' . $ago;
            })
            ->editColumn('products', function ($orders) {
                $orderProducts = '';
                foreach ($orders->orderproducts as $product) {
                    if (isset($product->color) && isset($product->size)) {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br><span style="color:blue;"> Colour: ' . $product->color . ' , Size: ' . $product->size . '</span>';
                    } elseif (isset($product->size)) {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br><span style="color:blue;"> Size: ' . $product->size . '</span>';
                    } else if ($product->color) {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br><span style="color:blue;"> Colour: ' . $product->color . '<span>';
                    } else {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName;
                    }
                }
                return rtrim($orderProducts, '<br>');
            })
            ->editColumn('user', function ($orders) {
                if ($orders->users) {
                    return $orders->users->name;
                } else {
                    return 'reseller not assign';
                }
            })
            ->editColumn('courier', function ($orders) {
                if (isset($orders->couriers->courierName)) {
                    return $orders->couriers->courierName;
                } else {
                    return 'Not Selected';
                }
            })
            ->editColumn('notification', function ($orders) {
                if (isset($orders->comments)) {
                    $commentAgo = $orders->comments->created_at ? $orders->comments->created_at->diffForHumans() : '';
                    if (isset($orders->customerNote)) return $orders->comments->comment . '<br>' . $commentAgo . '<br><span style="color:red;font-weight:bold;">[ Note:' . $orders->customerNote . ' ]</span>';
                    return $orders->comments->comment . '<br>' . $commentAgo;
                } else {
                    return 'No Comments Now';
                }
            })
            ->addColumn('statusButton', function ($orders) {
                if (last(request()->segments()) == 'Paid') {
                    return '<span class="badge bg-soft-success text-success">Paid</span>';
                } else if (last(request()->segments()) == 'Return') {
                    return '<span class="badge bg-soft-danger text-danger">Return</span>';
                } else if (last(request()->segments()) == 'Lost') {
                    return '<span class="badge bg-soft-danger text-danger">Lost</span>';
                } else if (last(request()->segments()) == 'Pending Invoiced') {
                    return $orders->status = $this->statusList('Pending Invoiced', $orders->id);
                } else {
                    return $orders->status = $this->statusList($orders->status, $orders->id);
                }
            })
            ->addColumn('action', function ($orders) {
                return "<a href='javascript:void(0);' data-id='" . $orders->id . "' class='action-icon btn-editorder'> <i class='fas fa-1x fa-edit'></i></a>
                <a href='javascript:void(0);' data-id='" . $orders->id . "' class='action-icon btn-delete'> <i class='fas fa-trash-alt'></i></a>";
            })
            ->escapeColumns([])->make();
    }

    //all order

    public function orderdata(Request $request, $abc)
    {
        \Log::info('[ORDERDATA] Called with status: ' . $abc);

        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        $columns = $request->input('columns') ?? [];
        $status = $request->input('status');

        \Log::info('[ORDERDATA] Admin: ' . ($admin->name ?? 'unknown') . ', role: ' . ($admin->roles->pluck('name')->implode(',') ?? 'none'));

        if ($abc == 'orderall') {
            if ($admin->hasrole('Shop')) {
                $orders =  Order::with(
                    [
                        'orderproducts' => function ($query) {
                            $this->configureOrderProductsQuery($query);
                        },
                        'admins' => function ($query) {
                            $query->select('id', 'name');
                        },
                        'users' => function ($query) {
                            $query->select('id', 'name');
                        },
                        'couriers' => function ($query) {
                            $query->select('id', 'courierName');
                        },
                        'products' => function ($query) {
                            $query->select('id', 'ProductName', 'ProductResellerPrice');
                        },
                        'comments' => function ($query) {
                            $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                        },
                        'cities' => function ($query) {
                            $query->select('id', 'courier_id', 'cityName');
                        },
                        'zones' => function ($query) {
                            $query->select('id', 'courier_id', 'city_id', 'zoneName');
                        }
                    ]
                )->where('store_id', Auth::guard('admin')->user()->id)
                    ->join('customers', 'customers.order_id', '=', 'orders.id')
                    ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
            } else {
                if ($admin->hasrole('Manager')) {
                    if ($admin->add_by == 1) {
                        $orders =  Order::with(
                            [
                                'orderproducts' => function ($query) {
                                    $this->configureOrderProductsQuery($query);
                                },
                                'admins' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'users' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'couriers' => function ($query) {
                                    $query->select('id', 'courierName');
                                },
                                'products' => function ($query) {
                                    $query->select('id', 'ProductName', 'ProductResellerPrice');
                                },
                                'comments' => function ($query) {
                                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                },
                                'cities' => function ($query) {
                                    $query->select('id', 'courier_id', 'cityName');
                                },
                                'zones' => function ($query) {
                                    $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                }
                            ]
                        )
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                    } else {
                        $orders =  Order::with(
                            [
                                'orderproducts' => function ($query) {
                                    $this->configureOrderProductsQuery($query);
                                },
                                'admins' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'users' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'couriers' => function ($query) {
                                    $query->select('id', 'courierName');
                                },
                                'products' => function ($query) {
                                    $query->select('id', 'ProductName', 'ProductResellerPrice');
                                },
                                'comments' => function ($query) {
                                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                },
                                'cities' => function ($query) {
                                    $query->select('id', 'courier_id', 'cityName');
                                },
                                'zones' => function ($query) {
                                    $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                }
                            ]
                        )->where('store_id', $admin->add_by)
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                    }
                } else {
                    if ($admin->hasrole('Superadmin')) {
                        $orders =  Order::with(
                            [
                                'orderproducts' => function ($query) {
                                    $this->configureOrderProductsQuery($query);
                                },
                                'admins' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'users' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'couriers' => function ($query) {
                                    $query->select('id', 'courierName');
                                },
                                'products' => function ($query) {
                                    $query->select('id', 'ProductName', 'ProductResellerPrice');
                                },
                                'comments' => function ($query) {
                                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                },
                                'cities' => function ($query) {
                                    $query->select('id', 'courier_id', 'cityName');
                                },
                                'zones' => function ($query) {
                                    $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                }
                            ]
                        )
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                    } else {
                        if ($admin->add_by == 1) {
                            $orders =  Order::with(
                                [
                                    'orderproducts' => function ($query) {
                                        $this->configureOrderProductsQuery($query);
                                    },
                                    'admins' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'users' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'couriers' => function ($query) {
                                        $query->select('id', 'courierName');
                                    },
                                    'products' => function ($query) {
                                        $query->select('id', 'ProductName', 'ProductResellerPrice');
                                    },
                                    'comments' => function ($query) {
                                        $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                    },
                                    'cities' => function ($query) {
                                        $query->select('id', 'courier_id', 'cityName');
                                    },
                                    'zones' => function ($query) {
                                        $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                    }
                                ]
                            )->where('admin_id', Auth::guard('admin')->user()->id)
                                ->join('customers', 'customers.order_id', '=', 'orders.id')
                                ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                        } else {
                            $orders =  Order::with(
                                [
                                    'orderproducts' => function ($query) {
                                        $this->configureOrderProductsQuery($query);
                                    },
                                    'admins' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'users' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'couriers' => function ($query) {
                                        $query->select('id', 'courierName');
                                    },
                                    'products' => function ($query) {
                                        $query->select('id', 'ProductName', 'ProductResellerPrice');
                                    },
                                    'comments' => function ($query) {
                                        $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                    },
                                    'cities' => function ($query) {
                                        $query->select('id', 'courier_id', 'cityName');
                                    },
                                    'zones' => function ($query) {
                                        $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                    }
                                ]
                            )->where('store_id', $admin->add_by)
                                ->join('customers', 'customers.order_id', '=', 'orders.id')
                                ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                        }
                    }
                }
            }
        } else {
            if ($admin->hasrole('Shop')) {
                $orders =  Order::with(
                    [
                        'orderproducts' => function ($query) {
                            $this->configureOrderProductsQuery($query);
                        },
                        'admins' => function ($query) {
                            $query->select('id', 'name');
                        },
                        'couriers' => function ($query) {
                            $query->select('id', 'courierName');
                        },
                        'comments' => function ($query) {
                            $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                        },
                        'cities' => function ($query) {
                            $query->select('id', 'courier_id', 'cityName');
                        },
                        'zones' => function ($query) {
                            $query->select('id', 'courier_id', 'city_id', 'zoneName');
                        }
                    ]
                )->where('store_id', Auth::guard('admin')->user()->id)
                    ->join('customers', 'customers.order_id', '=', 'orders.id')
                    ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
            } else {
                if ($admin->hasrole('Manager')) {
                    if ($admin->add_by == 1) {
                        $orders =  Order::with(
                            [
                                'orderproducts' => function ($query) {
                                    $this->configureOrderProductsQuery($query);
                                },
                                'admins' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'users' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'couriers' => function ($query) {
                                    $query->select('id', 'courierName');
                                },
                                'products' => function ($query) {
                                    $query->select('id', 'ProductName', 'ProductResellerPrice');
                                },
                                'comments' => function ($query) {
                                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                },
                                'cities' => function ($query) {
                                    $query->select('id', 'courier_id', 'cityName');
                                },
                                'zones' => function ($query) {
                                    $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                }
                            ]
                        )
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                    } else {
                        $orders =  Order::with(
                            [
                                'orderproducts' => function ($query) {
                                    $this->configureOrderProductsQuery($query);
                                },
                                'admins' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'users' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'couriers' => function ($query) {
                                    $query->select('id', 'courierName');
                                },
                                'products' => function ($query) {
                                    $query->select('id', 'ProductName', 'ProductResellerPrice');
                                },
                                'comments' => function ($query) {
                                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                },
                                'cities' => function ($query) {
                                    $query->select('id', 'courier_id', 'cityName');
                                },
                                'zones' => function ($query) {
                                    $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                }
                            ]
                        )->where('store_id', $admin->add_by)
                            ->join('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                    }
                } else {
                    if ($admin->hasrole('Executive')) {
                        if ($admin->add_by == 1) {
                            $orders =  Order::with(
                                [
                                    'orderproducts' => function ($query) {
                                        $this->configureOrderProductsQuery($query);
                                    },
                                    'admins' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'users' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'couriers' => function ($query) {
                                        $query->select('id', 'courierName');
                                    },
                                    'products' => function ($query) {
                                        $query->select('id', 'ProductName', 'ProductResellerPrice');
                                    },
                                    'comments' => function ($query) {
                                        $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                    },
                                    'cities' => function ($query) {
                                        $query->select('id', 'courier_id', 'cityName');
                                    },
                                    'zones' => function ($query) {
                                        $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                    }
                                ]
                            )->where('admin_id', Auth::guard('admin')->user()->id)
                                ->join('customers', 'customers.order_id', '=', 'orders.id')
                                ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                        } else {
                            $orders =  Order::with(
                                [
                                    'orderproducts' => function ($query) {
                                        $this->configureOrderProductsQuery($query);
                                    },
                                    'admins' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'users' => function ($query) {
                                        $query->select('id', 'name');
                                    },
                                    'couriers' => function ($query) {
                                        $query->select('id', 'courierName');
                                    },
                                    'products' => function ($query) {
                                        $query->select('id', 'ProductName', 'ProductResellerPrice');
                                    },
                                    'comments' => function ($query) {
                                        $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                    },
                                    'cities' => function ($query) {
                                        $query->select('id', 'courier_id', 'cityName');
                                    },
                                    'zones' => function ($query) {
                                        $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                    }
                                ]
                            )->where('store_id', $admin->add_by)
                                ->join('customers', 'customers.order_id', '=', 'orders.id')
                                ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                        }
                    } else {
                        $orders =  Order::with(
                            [
                                'orderproducts' => function ($query) {
                                    $this->configureOrderProductsQuery($query);
                                },
                                'admins' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'users' => function ($query) {
                                    $query->select('id', 'name');
                                },
                                'couriers' => function ($query) {
                                    $query->select('id', 'courierName');
                                },
                                'products' => function ($query) {
                                    $query->select('id', 'ProductName', 'ProductResellerPrice');
                                },
                                'comments' => function ($query) {
                                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                                },
                                'cities' => function ($query) {
                                    $query->select('id', 'courier_id', 'cityName');
                                },
                                'zones' => function ($query) {
                                    $query->select('id', 'courier_id', 'city_id', 'zoneName');
                                }
                                ]
                            )
                            ->leftJoin('customers', 'customers.order_id', '=', 'orders.id')
                            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress');
                    }
                }
            }
        }




        if ($abc != 'orderall') {
            $orders = $orders->where('orders.status', 'like', $abc);
        }

        // Global Search
        $globalSearch = $request->input('search.value');
        if (!empty($globalSearch)) {
            $orders = $orders->where(function ($q) use ($globalSearch) {
                $q->where('orders.invoiceID', 'like', "%{$globalSearch}%")
                  ->orWhere('customers.customerName', 'like', "%{$globalSearch}%")
                  ->orWhere('customers.customerPhone', 'like', "%{$globalSearch}%");
            });
        }

        $colSearch = function ($index) use ($columns) {
            return $columns[$index]['search']['value'] ?? null;
        };
        $s1  = $colSearch(1);
        $s2  = $colSearch(2);
        $s5  = $colSearch(5);
        $s8  = $colSearch(8);
        $s10 = $colSearch(10);

        if ($s1) {
            $orders = $orders->where(function ($q) use ($s1) {
                $q->where('orders.invoiceID', 'like', "%{$s1}%")
                  ->orWhere('orders.web_ID', 'like', "%{$s1}%");
            });
        }
        if ($s2) {
            $orders = $orders->where('customers.customerPhone', 'like', "%{$s2}%");
        }
        if ($s5) {
            $orders = $orders->where('orders.courier_id', '=', $s5);
        }
        if ($s8) {
            $orders = $orders->where('orders.orderDate', 'like', "%{$s8}%");
        }

        if ($admin->hasRole('Executive')) {
            if ($s10) {
                $orders = $orders->where('orders.memo', '=', $s10);
            }
        } else {
            if ($s10) {
                $orders = $orders->where('orders.user_id', '=', $s10);
            }
        }

        return Datatables::of($orders->orderBy('orders.updated_at', 'DESC'))
            ->addColumn('customerInfo', function ($orders) {
                $name = $orders->customerName ?? '—';
                $phone = $orders->customerPhone ?? '—';
                $address = $orders->customerAddress ?? '—';
                $entry = $orders->entry_complete ?? '';
                return $name . '<br>' . $phone . '<br>' . $address . '<br> <span style="color:red;font-weight:bold;">' . $entry . '</span><br><button class="btn btn-success btn-sm" style="margin: 4px;padding: 0px 4px;" data-num="' . ($orders->customerPhone ?? '') . '" data-inv="' . $orders->invoiceID . '" id="checkfraud">Check</button>';
            })
            ->addColumn('invoice', function ($orders) {
                $ago = $orders->updated_at ? $orders->updated_at->diffForHumans() : '';
                return '<a href="' . env('APP_URL') . 'admin_order/invoice-view/' . $orders->invoiceID . '" target="_blank"> ' . $orders->invoiceID . '<a><br>' . ($orders->web_ID ?? '') . '<br>' . $ago;
            })
            ->editColumn('products', function ($orders) {
                $orderProducts = '';
                foreach ($orders->orderproducts as $product) {
                    // Product name + qty line
                    if (isset($product->color) && isset($product->size)) {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br><span style="color:blue;"> Colour: ' . $product->color . ' , Size: ' . $product->size . '</span>';
                    } elseif (isset($product->size)) {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br><span style="color:blue;"> Size: ' . $product->size . '</span>';
                    } else if ($product->color) {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br><span style="color:blue;"> Colour: ' . $product->color . '<span>';
                    } else {
                        $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName;
                    }

                    // Vendor badge
                    $vendorName = $product->product && $product->product->vendor ? $product->product->vendor->company_name : null;
                    if ($vendorName) {
                        $orderProducts .= ' <span style="background:#7c3aed;color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;">' . $vendorName . '</span>';
                    }

                    // Fulfillment status badge
                    $fs = $product->fulfillment_status ?? 'pending';
                    if ($vendorName) {
                        $badgeColors = [
                            'pending' => 'background:#6b7280;color:#fff;',
                            'awaiting_shipment' => 'background:#f59e0b;color:#fff;',
                            'shipped' => 'background:#10b981;color:#fff;',
                        ];
                        $style = $badgeColors[$fs] ?? $badgeColors['pending'];
                        $label = str_replace('_', ' ', ucfirst($fs));
                        $orderProducts .= ' <span style="' . $style . 'padding:1px 6px;border-radius:4px;font-size:10px;">' . $label . '</span>';

                        if ($product->tracking_number) {
                            $orderProducts .= '<br><span style="color:#059669;font-size:11px;">📦 ' . $product->tracking_number . '</span>';
                        }
                    }

                    $orderProducts .= '<br>';
                }
                return rtrim($orderProducts, '<br>');
            })
            ->editColumn('user', function ($orders) {
                if ($orders->users) {
                    return $orders->users->name;
                } else {
                    return 'reseller not assign';
                }
            })
            ->editColumn('courier', function ($orders) {
                if (isset($orders->couriers->courierName) && isset($orders->cities->cityName) && isset($orders->zones->zoneName)) {
                    return $orders->couriers->courierName . '<br>' . $orders->cities->cityName . '<br>' . $orders->zones->zoneName;
                } elseif (isset($orders->couriers->courierName) && isset($orders->cities->cityName)) {
                    return $orders->couriers->courierName . '<br>' . $orders->cities->cityName;
                } elseif (isset($orders->couriers->courierName) && isset($orders->zones->zoneName)) {
                    return $orders->couriers->courierName . '<br>' . $orders->zones->zoneName;
                } elseif (isset($orders->couriers->courierName)) {
                    return $orders->couriers->courierName;
                } else {
                    return 'Not Selected';
                }
            })
            ->editColumn('notification', function ($orders) {
                if (isset($orders->comments)) {
                    $commentAgo = $orders->comments->created_at ? $orders->comments->created_at->diffForHumans() : '';
                    if (isset($orders->customerNote)) return $orders->comments->comment . '<br>' . $commentAgo . '<br><span style="color:red;font-weight:bold;">[ Note:' . $orders->customerNote . ' ]</span>';
                    return $orders->comments->comment . '<br>' . $commentAgo;
                } else {
                    return 'No Comments Now';
                }
            })
            ->addColumn('resellprice', function ($orders) {
                if (isset($orders->trackingLink)) {
                    return $orders->subTotal - $orders->profit . "<br><a href='" . $orders->trackingLink . "' target='_blank' class='mt-2 btn btn-info action-icon btn-viecourier btn-sm'> Track </a>";
                } else {
                    return $orders->subTotal - $orders->profit;
                }
            })
            ->addColumn('wholesale', function ($orders) {
                $wholesale = 0;
                foreach ($orders->orderproducts as $product) {
                    $expss = Product::where('id', $product->product_id)->first();
                    if (isset($expss)) {
                        $wholesale += $product->quantity * $expss->ProductWholesalePrice;
                    } else {
                        $wholesale += 0;
                    }
                }
                return $wholesale;
            })
            ->addColumn('statusButton', function ($orders) {

                return $orders->status = $this->statusList($orders->status, $orders->id);
            })
            ->addColumn('action', function ($orders) {
    $viewUrl = url('admin_order/view/' . $orders->id);

    // Check if order has vendor products
    $hasVendorProducts = $orders->orderproducts->contains(function ($op) {
        return $op->product && $op->product->vendor_id;
    });

    $notifyBtn = '';
    if ($hasVendorProducts) {
        $notifyBtn = "
        <a href='javascript:void(0);'
           data-id='{$orders->id}'
           class='action-icon btn-notify-vendor'
           title='Notify Vendor to Ship'
           style='color:#7c3aed;'>
            <i class='fas fa-truck' style='font-size: 20px; padding-right: 15px;'></i>
        </a>";
    }

    return "
        <a href='{$viewUrl}' class='action-icon me-2' title='View Order'>
            <i class='fas fa-eye' style='font-size: 20px;'></i>
        </a>
        {$notifyBtn}
        <a href='javascript:void(0);'
           data-id='{$orders->id}'
           class='action-icon btn-editorder me-2'
           title='Edit Order'>
            <i class='fas fa-edit' style='font-size: 20px;'></i>
        </a>
        <a href='javascript:void(0);'
           data-id='{$orders->id}'
           class='action-icon btn-delete'
           title='Delete Order'>
            <i class='fas fa-trash-alt' style='font-size: 20px;'></i>
        </a>
    ";
})
            ->escapeColumns([])->make();
    }


    //update status
    public function updateorderstatus(Request $request)
    {
        $id = $request['id'];

        $status = $request['status'];
        $order = Order::find($id);
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();

        if ($admin->hasRole('Shop')) {
            if ($status == 'Delivered') {
                $response['status'] = 'failed';
                $response['message'] = 'You do not have permission to update status in delivered';
                return json_encode($response);
            }
        }

        $customer = Customer::where('order_id', $order->id)->first();
        if ($order->status != 'Canceled' && $status == 'Canceled') {
            // Restore product stock on cancellation
            app(\App\Services\StockService::class)->restoreForOrder($order->id);

            $user = User::where('id', $order->user_id)->first();
            $user->account_balance = $user->account_balance + $order->paymentAmount;
            $user->update();
            $comment = new Comment();
            $comment->order_id = $id;
            $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Canceled.Please contact support';
            $comment->admin_id = Auth::guard('admin')->user()->id;
            $comment->user_id = $order->user_id;
            $comment->status = 1;
            $comment->type = 'Canceled';
            $comment->save();
        }

        if ($order->status == 'Canceled' && $status != 'Canceled') {
            // Re-decrement stock when order is un-cancelled
            app(\App\Services\StockService::class)->decrementForOrder($order->id);

            $user = User::where('id', $order->user_id)->first();
            $user->account_balance = $user->account_balance - $order->deliveryCharge;
            $user->update();
            $comment = new Comment();
            $comment->order_id = $id;
            $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Reassign for delivery';
            $comment->admin_id = Auth::guard('admin')->user()->id;
            $comment->user_id = $order->user_id;
            $comment->status = 1;
            $comment->type = 'Reassign';
            $comment->save();
        }

        if ($request['status'] == 'Confirmed') {

            $order->orderDate = date('Y-m-d');
        }

        if ($order->status != 'Return' && $request['status'] == 'Return') {
            // Restore product stock on return
            app(\App\Services\StockService::class)->restoreForOrder($order->id);

            $comment = new Comment();
            $comment->order_id = $id;
            $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Return The Parcel';
            $comment->admin_id = Auth::guard('admin')->user()->id;
            $comment->user_id = $order->user_id;
            $comment->status = 1;
            $comment->type = 'Return';
            $comment->save();
            $order->completeDate = date('Y-m-d');
        }

        if ($order->status == 'Delivered') {
            if ($request['status'] == 'Delivered') {
            } else {
                // Reverse delivery credits (status moving away from Delivered)
                app(\App\Services\OrderDeliveryService::class)->reverseDelivery($order);
            }
        } else {
            if ($order->status != 'Delivered' && $request['status'] == 'Delivered') {
                // Admin-specific comment with admin_id
                $comment = new Comment();
                $comment->order_id = $id;
                $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Delivered Successfully';
                $comment->admin_id = Auth::guard('admin')->user()->id;
                $comment->user_id = $order->user_id;
                $comment->status = 1;
                $comment->type = 'Delivered';
                $comment->save();

                $order->deliveryDate = date('Y-m-d');

                // Use centralized delivery service for all financial operations
                app(\App\Services\OrderDeliveryService::class)->markDelivered($order);
            }
        }

        $order->status = $status;
        $result = $order->save();
        if ($result) {
            $response['status'] = 'success';
            $response['message'] = 'Successfully Update Status to ' . $request['status'];
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to update Status ' . $request['status'];
        }

        $comment = new Comment();
        $comment->order_id = $id;
        $comment->comment = Auth::guard('admin')->user()->name . ' Successfully Update #SS00' . $id . ' Order status to ' . $status;
        $comment->admin_id = Auth::guard('admin')->user()->id;
        $comment->status = 1;
        $comment->save();

        return json_encode($response);
    }

    /**
     * Notify vendor(s) to ship products for the given order(s).
     * Sets fulfillment_status to 'awaiting_shipment' on vendor order products.
     */
    public function notifyVendorToShip(Request $request)
    {
        $orderIds = $request->input('order_ids', []);
        if (empty($orderIds)) {
            return response()->json(['status' => 'failed', 'message' => 'No orders selected'], 400);
        }

        $updated = 0;
        foreach ($orderIds as $orderId) {
            $order = Order::with('orderproducts.product')->find($orderId);
            if (!$order) continue;

            foreach ($order->orderproducts as $op) {
                if ($op->product && $op->product->vendor_id) {
                    $fs = $op->fulfillment_status ?? 'pending';
                    if ($fs === 'pending') {
                        $op->fulfillment_status = 'awaiting_shipment';
                        $op->save();
                        $updated++;
                    }
                }
            }

            // Add comment
            $comment = new Comment();
            $comment->order_id = $orderId;
            $comment->comment = Auth::guard('admin')->user()->name . ' notified vendor(s) to ship products for order #SS00' . $orderId;
            $comment->admin_id = Auth::guard('admin')->user()->id;
            $comment->status = 1;
            $comment->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => $updated . ' vendor product(s) notified to ship across ' . count($orderIds) . ' order(s)',
        ]);
    }

    public function statusList($status, $id)
    {
        $allStatus = array(
            'order' => array(
                "Pending" => array(
                    "name" => "Pending",
                    "icon" => "fe-tag",
                    "color" => " bg-baguni"
                ),
                "Canceled" => array(
                    "name" => "Canceled",
                    "icon" => "fe-trash-2",
                    "color" => " bg-danger"
                ),
                "Confirmed" => array(
                    "name" => "Confirmed",
                    "icon" => "fe-check-circle",
                    "color" => " bg-confirmed"
                ),
                "Processing" => array(
                    "name" => "Processing",
                    "icon" => "fe-tag",
                    "color" => " bg-baguni"
                ),
                "Packageing" => array(
                    "name" => "Packageing",
                    "color" => " bg-primary"
                ),
                "Shipped" => array(
                    "name" => "Shipped",
                    "color" => " bg-info"
                ),
                "Ontheway" => array(
                    "name" => "Ontheway",
                    "color" => " bg-ondv"
                ),
                "Delivered" => array(
                    "name" => "Delivered",
                    "color" => " bg-delivered"
                ),
                "Return" => array(
                    "name" => "Return",
                    "color" => " bg-return"
                )

            ),

        );

        $temp = 'order';
        foreach ($allStatus as $key => $value) {
            foreach ($value as $kes => $val) {
                if ($kes == $status) {
                    $temp = $key;
                }
            }
        }
        $args = $allStatus[$temp];

        // Fallback for unknown statuses
        if (!isset($args[$status])) {
            return "<span class='badge bg-secondary'>" . e($status) . "</span>";
        }

        $html = '';
        foreach ($args as $value) {
            if ($args[$status]['name'] != $value['name']) {

                $html = $html . "<a class=' btn-sm dropdown-item btn-status' data-id='" . $id . "' data-status='" . $value['name'] . "' href='#'>" . $value['name'] . "</a>";
            }
        }
        $response = "<div class='btn-group dropdown'>
            <a href='javascript: void(0);' style='color:white'  class=' btn-sm table-action-btn dropdown-toggle arrow-none btn" . $args[$status]['color'] . " btn-xs' data-bs-toggle='dropdown' aria-expanded='false' >" . $args[$status]['name'] . " <i class='mdi mdi-chevron-down'></i></a>
            <div class='dropdown-menu dropdown-menu-right'>
            " . $html . "
            </div>
        </div>";

        return $response;
    }

    public function courier(Request $request)
    {
        if (isset($request['q'])) {
            $couriers = Courier::query()->where([
                ['courierName', 'like', '%' . $request['q'] . '%'],
                ['status', 'like', 'Active']
            ])->get();
        } else {
            $couriers = Courier::query()->where('status', 'like', 'Active')->get();
        }
        $courier = array();
        foreach ($couriers as $item) {
            $courier[] = array(
                "id" => $item['id'],
                "text" => $item['courierName']
            );
        }
        return json_encode($courier);
    }

    //get users
    public function users(Request $request)
    {
        if (isset($request['q'])) {
            $users = User::where('status', 'Active')->where('membership_status', 'Paid')->query()->where([['name', 'like', '%' . $request['q'] . '%']])->get();
        } else {
            $users = User::where('status', 'Active')->where('membership_status', 'Paid')->get();
        }
        $user = array();
        foreach ($users as $item) {
            $user[] = array(
                "id" => $item['id'],
                "text" => $item['name']
            );
        }
        return json_encode($user);
        die();
    }

    public function shops(Request $request)
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        if (false && $admin->hasRole('Shop')) {
            $users = Admin::where('email', Auth::guard('admin')->user()->email)->get();
        } else {
            if (isset($request['q'])) {
                $users = Admin::whereHas('roles', function ($q) {
                    $q->where('name', 'Shop');
                })->where('status', 'Active')->query()->where([['name', 'like', '%' . $request['q'] . '%']])->get();
            } else {
                $users = Admin::whereHas('roles', function ($q) {
                    $q->where('name', 'Shop');
                })->where('status', 'Active')->get();
            }
        }
        $user = array();
        foreach ($users as $item) {
            $user[] = array(
                "id" => $item['id'],
                "text" => $item['name'] . '(' . $item['shop_name'] . ')',
            );
        }
        return json_encode($user);
        die();
    }




    public function countorder()
    {
        $adm = Admin::where('email', Auth::guard('admin')->user()->email)->first();

        if ($adm->hasrole('Shop')) {
            $response['allorder'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->count();
            $response['all'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->count();
            $response['pending'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Pending')->count();
            $response['canceled'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Canceled')->count();
            $response['confirmed'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Confirmed')->count();
            $response['processing'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Processing')->count();
            $response['packageing'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Packageing')->count();
            $response['ontheway'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Ontheway')->count();
            $response['delivered'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Delivered')->count();
            $response['return'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Return')->count();
        } else {
            if ($adm->hasrole('Manager')) {
                if ($adm->add_by == 1) {
                    $response['allorder'] = DB::table('orders')->count();
                    $response['all'] = DB::table('orders')->count();
                    $response['pending'] = DB::table('orders')->where('status', 'like', 'Pending')->count();
                    $response['canceled'] = DB::table('orders')->where('status', 'like', 'Canceled')->count();
                    $response['confirmed'] = DB::table('orders')->where('status', 'like', 'Confirmed')->count();
                    $response['processing'] = DB::table('orders')->where('status', 'like', 'Processing')->count();
                    $response['packageing'] = DB::table('orders')->where('status', 'like', 'Packageing')->count();
                    $response['ontheway'] = DB::table('orders')->where('status', 'like', 'Ontheway')->count();
                    $response['delivered'] = DB::table('orders')->where('status', 'like', 'Delivered')->count();
                    $response['return'] = DB::table('orders')->where('status', 'like', 'Return')->count();
                } else {
                    $response['allorder'] = DB::table('orders')->where('store_id', $adm->add_by)->count();
                    $response['all'] = DB::table('orders')->where('store_id', $adm->add_by)->count();
                    $response['pending'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Pending')->count();
                    $response['canceled'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Canceled')->count();
                    $response['confirmed'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Confirmed')->count();
                    $response['processing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Processing')->count();
                    $response['packageing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Packageing')->count();
                    $response['ontheway'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Ontheway')->count();
                    $response['delivered'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Delivered')->count();
                    $response['return'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Return')->count();
                }
            } else {
                if ($adm->hasrole('Superadmin')) {
                    $response['allorder'] = DB::table('orders')->count();
                    $response['all'] = DB::table('orders')->count();
                    $response['pending'] = DB::table('orders')->where('status', 'like', 'Pending')->count();
                    $response['canceled'] = DB::table('orders')->where('status', 'like', 'Canceled')->count();
                    $response['confirmed'] = DB::table('orders')->where('status', 'like', 'Confirmed')->count();
                    $response['processing'] = DB::table('orders')->where('status', 'like', 'Processing')->count();
                    $response['packageing'] = DB::table('orders')->where('status', 'like', 'Packageing')->count();
                    $response['ontheway'] = DB::table('orders')->where('status', 'like', 'Ontheway')->count();
                    $response['delivered'] = DB::table('orders')->where('status', 'like', 'Delivered')->count();
                    $response['return'] = DB::table('orders')->where('status', 'like', 'Return')->count();
                } else {
                    if ($adm->add_by == 1) {
                        $response['allorder'] = DB::table('orders')->count();
                        $response['all'] = DB::table('orders')->count();
                        $response['pending'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Pending')->count();
                        $response['canceled'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Canceled')->count();
                        $response['confirmed'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Confirmed')->count();
                        $response['processing'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Processing')->count();
                        $response['packageing'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Packageing')->count();
                        $response['ontheway'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Ontheway')->count();
                        $response['delivered'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Delivered')->count();
                        $response['return'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Return')->count();
                    } else {
                        $response['allorder'] = DB::table('orders')->where('store_id', $adm->add_by)->count();
                        $response['all'] = DB::table('orders')->where('store_id', $adm->add_by)->count();
                        $response['pending'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Pending')->count();
                        $response['canceled'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Canceled')->count();
                        $response['confirmed'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Confirmed')->count();
                        $response['processing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Processing')->count();
                        $response['packageing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Packageing')->count();
                        $response['ontheway'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Ontheway')->count();
                        $response['delivered'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Delivered')->count();
                        $response['return'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Return')->count();
                    }
                }
            }
        }

        $response['status'] = 'success';
        return json_encode($response);
    }

    public function salescount(Request $request)
    {
        $start = $request->startDate;
        $end = $request->endDate;

        $orders = DB::table('orders')->where('status', 'Delivered')->whereBetween('deliveryDate', [$start, $end])->get();
        $sales = 0;
        $purchese = 0;
        foreach ($orders as $order) {
            $sales += $order->subTotal - $order->profit;
            $orderproducts = Orderproduct::where('order_id', $order->id)->get();
            foreach ($orderproducts as $orderproduct) {
                $expss = Product::where('id', $orderproduct->product_id)->first();
                if (isset($expss)) {
                    $purchese += $orderproduct->quantity * $expss->ProductWholesalePrice;
                } else {
                    $purchese += 0;
                }
            }
        }

        $profit = $sales - $purchese;

        $pendingorders = DB::table('orders')->whereIn('status', ['Processing', 'Ontheway', 'Confirmed', 'Packageing'])->whereBetween('orderDate', [$start, $end])->get();
        $pendingsales = 0;
        $pendingpurchese = 0;
        foreach ($pendingorders as $pendingorder) {
            $pendingsales += $pendingorder->subTotal - $pendingorder->profit;
            $pendingorderproducts = Orderproduct::where('order_id', $pendingorder->id)->get();
            foreach ($pendingorderproducts as $pendingorderproduct) {
                $expss = Product::where('id', $pendingorderproduct->product_id)->first();
                if (isset($expss)) {
                    $pendingpurchese += $pendingorderproduct->quantity * $expss->ProductWholesalePrice;
                } else {
                    $pendingpurchese += 0;
                }
            }
        }

        $pendingprofit = $pendingsales - $pendingpurchese;

        $response['profit'] = $profit;
        $response['pendingprofit'] = $pendingprofit;
        $response['order'] = $orders->count();
        $response['pendingorder'] = $pendingorders->count();

        $response['resellerprofit'] = DB::table('orders')->where('status', 'Delivered')->whereBetween('deliveryDate', [$start, $end])->get()->sum('profit');
        $response['resellerorder'] = DB::table('orders')->where('status', 'Delivered')->whereBetween('deliveryDate', [$start, $end])->get()->count();
        $response['resellerpendingprofit'] = DB::table('orders')->whereIn('status', ['Processing', 'Ontheway', 'Confirmed', 'Packageing'])->whereBetween('orderDate', [$start, $end])->get()->sum('profit');
        $response['resellerpendingorder'] = DB::table('orders')->whereIn('status', ['Processing', 'Ontheway', 'Confirmed', 'Packageing'])->whereBetween('orderDate', [$start, $end])->get()->count();

        $response['totalprofit'] = $profit + $response['resellerprofit'];
        $response['totalpendingprofit'] = $pendingprofit + $response['resellerpendingprofit'];
        $response['totalpendingorder'] = $pendingorders->count() + $response['resellerpendingorder'];
        $response['totalorder'] = $orders->count() + $response['resellerorder'];

        $response['status'] = 'success';
        return json_encode($response);
    }

    public function infocount(Request $request)
    {
        $adm = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        $start = $request->startDate;
        $end = $request->endDate;
        if ($adm->hasrole('Shop')) {
            $response['allorder'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->whereBetween('orderDate', [$start, $end])->count();
            $response['all'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->whereBetween('orderDate', [$start, $end])->count();
            $response['pending'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Pending')->whereBetween('orderDate', [$start, $end])->count();
            $response['canceled'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Canceled')->whereBetween('orderDate', [$start, $end])->count();
            $response['confirmed'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Confirmed')->whereBetween('orderDate', [$start, $end])->count();
            $response['processing'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Processing')->whereBetween('orderDate', [$start, $end])->count();
            $response['packageing'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Packageing')->whereBetween('orderDate', [$start, $end])->count();
            $response['ontheway'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Ontheway')->whereBetween('orderDate', [$start, $end])->count();
            $response['delivered'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Delivered')->whereBetween('orderDate', [$start, $end])->count();
            $response['return'] = DB::table('orders')->where('store_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Return')->whereBetween('orderDate', [$start, $end])->count();
        } else {
            if ($adm->hasrole('Manager')) {
                if ($adm->add_by == 1) {
                    $response['allorder'] = DB::table('orders')->whereBetween('orderDate', [$start, $end])->count();
                    $response['all'] = DB::table('orders')->whereBetween('orderDate', [$start, $end])->count();
                    $response['pending'] = DB::table('orders')->where('status', 'like', 'Pending')->whereBetween('orderDate', [$start, $end])->count();
                    $response['canceled'] = DB::table('orders')->where('status', 'like', 'Canceled')->whereBetween('orderDate', [$start, $end])->count();
                    $response['confirmed'] = DB::table('orders')->where('status', 'like', 'Confirmed')->whereBetween('orderDate', [$start, $end])->count();
                    $response['processing'] = DB::table('orders')->where('status', 'like', 'Processing')->whereBetween('orderDate', [$start, $end])->count();
                    $response['packageing'] = DB::table('orders')->where('status', 'like', 'Packageing')->whereBetween('orderDate', [$start, $end])->count();
                    $response['ontheway'] = DB::table('orders')->where('status', 'like', 'Ontheway')->whereBetween('orderDate', [$start, $end])->count();
                    $response['delivered'] = DB::table('orders')->where('status', 'like', 'Delivered')->whereBetween('orderDate', [$start, $end])->whereBetween('orderDate', [$start, $end])->whereBetween('orderDate', [$start, $end])->count();
                    $response['return'] = DB::table('orders')->where('status', 'like', 'Return')->whereBetween('orderDate', [$start, $end])->count();
                } else {
                    $response['allorder'] = DB::table('orders')->where('store_id', $adm->add_by)->whereBetween('orderDate', [$start, $end])->count();
                    $response['all'] = DB::table('orders')->where('store_id', $adm->add_by)->whereBetween('orderDate', [$start, $end])->count();
                    $response['pending'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Pending')->whereBetween('orderDate', [$start, $end])->count();
                    $response['canceled'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Canceled')->whereBetween('orderDate', [$start, $end])->count();
                    $response['confirmed'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Confirmed')->whereBetween('orderDate', [$start, $end])->count();
                    $response['processing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Processing')->whereBetween('orderDate', [$start, $end])->count();
                    $response['packageing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Packageing')->whereBetween('orderDate', [$start, $end])->count();
                    $response['ontheway'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Ontheway')->whereBetween('orderDate', [$start, $end])->count();
                    $response['delivered'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Delivered')->whereBetween('orderDate', [$start, $end])->count();
                    $response['return'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Return')->whereBetween('orderDate', [$start, $end])->count();
                }
            } else {
                if ($adm->hasrole('Superadmin')) {
                    $response['allorder'] = DB::table('orders')->whereBetween('orderDate', [$start, $end])->count();
                    $response['all'] = DB::table('orders')->whereBetween('orderDate', [$start, $end])->count();
                    $response['pending'] = DB::table('orders')->where('status', 'like', 'Pending')->whereBetween('orderDate', [$start, $end])->count();
                    $response['canceled'] = DB::table('orders')->where('status', 'like', 'Canceled')->whereBetween('orderDate', [$start, $end])->count();
                    $response['confirmed'] = DB::table('orders')->where('status', 'like', 'Confirmed')->whereBetween('orderDate', [$start, $end])->count();
                    $response['processing'] = DB::table('orders')->where('status', 'like', 'Processing')->whereBetween('orderDate', [$start, $end])->count();
                    $response['packageing'] = DB::table('orders')->where('status', 'like', 'Packageing')->whereBetween('orderDate', [$start, $end])->count();
                    $response['ontheway'] = DB::table('orders')->where('status', 'like', 'Ontheway')->whereBetween('orderDate', [$start, $end])->count();
                    $response['delivered'] = DB::table('orders')->where('status', 'like', 'Delivered')->whereBetween('orderDate', [$start, $end])->count();
                    $response['return'] = DB::table('orders')->where('status', 'like', 'Return')->whereBetween('orderDate', [$start, $end])->count();
                    $response['too'] = DB::table('orders')->whereIn('status', ['Pending', 'Canceled', 'Processing', 'Packageing'])->whereBetween('orderDate', [$start, $end])->count();

                    $response['toa'] = DB::table('orders')->whereBetween('orderDate', [$start, $end])->sum('subTotal');
                    $response['tca'] = DB::table('orders')->where('status', 'like', 'Confirmed')->whereBetween('orderDate', [$start, $end])->sum('subTotal');
                    $response['toda'] = DB::table('orders')->where('status', 'like', 'Ontheway')->whereBetween('orderDate', [$start, $end])->sum('subTotal');
                    $response['tda'] = DB::table('orders')->where('status', 'like', 'Delivered')->whereBetween('orderDate', [$start, $end])->sum('subTotal');
                    $response['tra'] = DB::table('orders')->where('status', 'like', 'Return')->whereBetween('orderDate', [$start, $end])->sum('subTotal');
                    $response['tooa'] = DB::table('orders')->whereIn('status', ['Pending', 'Canceled', 'Processing', 'Packageing'])->whereBetween('orderDate', [$start, $end])->sum('subTotal');
                } else {
                    if ($adm->add_by == 1) {
                        $response['allorder'] = DB::table('orders')->whereBetween('orderDate', [$start, $end])->count();
                        $response['all'] = DB::table('orders')->whereBetween('orderDate', [$start, $end])->count();
                        $response['pending'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Pending')->whereBetween('orderDate', [$start, $end])->count();
                        $response['canceled'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Canceled')->whereBetween('orderDate', [$start, $end])->count();
                        $response['confirmed'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Confirmed')->whereBetween('orderDate', [$start, $end])->count();
                        $response['processing'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Processing')->whereBetween('orderDate', [$start, $end])->count();
                        $response['packageing'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Packageing')->whereBetween('orderDate', [$start, $end])->count();
                        $response['ontheway'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Ontheway')->whereBetween('orderDate', [$start, $end])->count();
                        $response['delivered'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Delivered')->whereBetween('orderDate', [$start, $end])->count();
                        $response['return'] = DB::table('orders')->where('admin_id', Auth::guard('admin')->user()->id)->where('status', 'like', 'Return')->whereBetween('orderDate', [$start, $end])->count();
                    } else {
                        $response['allorder'] = DB::table('orders')->where('store_id', $adm->add_by)->whereBetween('orderDate', [$start, $end])->count();
                        $response['all'] = DB::table('orders')->where('store_id', $adm->add_by)->whereBetween('orderDate', [$start, $end])->count();
                        $response['pending'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Pending')->whereBetween('orderDate', [$start, $end])->count();
                        $response['canceled'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Canceled')->whereBetween('orderDate', [$start, $end])->count();
                        $response['confirmed'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Confirmed')->whereBetween('orderDate', [$start, $end])->count();
                        $response['processing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Processing')->whereBetween('orderDate', [$start, $end])->count();
                        $response['packageing'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Packageing')->whereBetween('orderDate', [$start, $end])->count();
                        $response['ontheway'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Ontheway')->whereBetween('orderDate', [$start, $end])->count();
                        $response['delivered'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Delivered')->whereBetween('orderDate', [$start, $end])->count();
                        $response['return'] = DB::table('orders')->where('store_id', $adm->add_by)->where('status', 'like', 'Return')->whereBetween('orderDate', [$start, $end])->count();
                    }
                }
            }
        }

        $response['status'] = 'success';
        return json_encode($response);
    }



    public function countorderbyid($id)
    {

        if ($id == 0) {
            $response['title'] = '/Today';
            $response['allorder'] = DB::table('orders')->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['all'] = DB::table('orders')->where('created_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['pending'] = DB::table('orders')->where('status', 'like', 'Pending')->where('created_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['canceled'] = DB::table('orders')->where('status', 'like', 'Canceled')->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['confirmed'] = DB::table('orders')->where('status', 'like', 'Confirmed')->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['invoiced'] = DB::table('orders')->where('status', 'like', 'Invoiced')->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['ondelivery'] = DB::table('orders')->where('status', 'like', 'On Delivery')->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['delivered'] = DB::table('orders')->whereIn('orders.status', ['Delivered', 'Customer Confirm', 'Customer On Hold', 'Request to Return'])->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            $response['return'] = DB::table('orders')->where('status', 'like', 'Return')->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->count();
            return json_encode($response);
        } else if ($id == 1) {
            $response['title'] = '/This Month';
            $response['allorder'] = DB::table('orders')->whereYear('updated_at',  '>=', Carbon::now()->year)->whereMonth('updated_at',  '>=', Carbon::now()->month)->count();
            $response['all'] = DB::table('orders')->where('created_at', '>=', Carbon::now()->month()->format('y-m-d'))->count();
            $response['pending'] = DB::table('orders')->where('status', 'like', 'Pending')->whereYear('created_at',  '>=', Carbon::now()->year)->whereMonth('created_at',  '>=', Carbon::now()->month)->count();
            $response['canceled'] = DB::table('orders')->where('status', 'like', 'Canceled')->whereYear('created_at',  '>=', Carbon::now()->year)->whereMonth('created_at',  '>=', Carbon::now()->month)->count();
            $response['confirmed'] = DB::table('orders')->where('status', 'like', 'Confirmed')->whereYear('updated_at',  '>=', Carbon::now()->year)->whereMonth('updated_at',  '>=', Carbon::now()->month)->count();
            $response['invoiced'] = DB::table('orders')->where('status', 'like', 'Invoiced')->whereYear('updated_at',  '>=', Carbon::now()->year)->whereMonth('updated_at',  '>=', Carbon::now()->month)->count();
            $response['ondelivery'] = DB::table('orders')->where('status', 'like', 'On Delivery')->whereYear('updated_at',  '>=', Carbon::now()->year)->whereMonth('updated_at',  '>=', Carbon::now()->month)->count();
            $response['delivered'] = DB::table('orders')->whereIn('orders.status', ['Delivered', 'Customer Confirm', 'Customer On Hold', 'Request to Return'])->whereYear('updated_at',  '>=', Carbon::now()->year)->whereMonth('updated_at',  '>=', Carbon::now()->month)->count();
            $response['return'] = DB::table('orders')->where('status', 'like', 'Return')->whereYear('updated_at',  '>=', Carbon::now()->year)->whereMonth('updated_at',  '>=', Carbon::now()->month)->count();
            $response['status'] = 'success';
            return json_encode($response);
        } else {
            $response['title'] = '/This Year';
            $response['allorder'] = DB::table('orders')->whereYear('updated_at',  '>=', Carbon::now()->year)->count();
            $response['all'] = DB::table('orders')->whereYear('created_at', '>=', Carbon::now()->year)->count();
            $response['pending'] = DB::table('orders')->where('status', 'like', 'Pending')->whereYear('created_at', '>=', Carbon::now()->year)->count();
            $response['canceled'] = DB::table('orders')->where('status', 'like', 'Canceled')->whereYear('updated_at', '>=', Carbon::now()->year)->count();
            $response['confirmed'] = DB::table('orders')->where('status', 'like', 'Confirmed')->whereYear('updated_at', '>=', Carbon::now()->year)->count();
            $response['invoiced'] = DB::table('orders')->where('status', 'like', 'Invoiced')->whereYear('updated_at', '>=', Carbon::now()->year)->count();
            $response['ondelivery'] = DB::table('orders')->where('status', 'like', 'On Delivery')->whereYear('updated_at', '>=', Carbon::now()->year)->count();
            $response['delivered'] = DB::table('orders')->whereIn('orders.status', ['Delivered', 'Customer Confirm', 'Customer On Hold', 'Request to Return'])->whereYear('updated_at', '>=', Carbon::now()->year)->count();
            $response['return'] = DB::table('orders')->where('status', 'like', 'Return')->whereYear('updated_at', '>=', Carbon::now()->year)->count();
            $response['status'] = 'success';
            return json_encode($response);
        }
    }

    //top sell product

    public function topsellpeoduct($id)
    {
        if ($id == 0) {
            $response['orders'] = DB::table('orders')->whereIn('orders.status', ['Pending Invoiced', 'Invoiced', 'Stock Out', 'Customer Confirm', 'Request to Return', 'Paid', 'Return', 'Lost', 'Completed', 'Delivered', 'Customer On Hold'])
                ->where('orders.created_at',  '>=', Carbon::today()->format('y-m-d'))
                ->join('orderproducts', 'orders.id', '=', 'orderproducts.order_id')
                ->select('orders.status', 'orders.orderDate', 'orderproducts.*', DB::raw('SUM(quantity) as total_amount'))
                ->groupBy('orderproducts.product_id')->orderBy('total_amount', 'desc')->get();
            $response['status'] = 'success';
            return json_encode($response);
        } else if ($id == 1) {
            $response['orders'] = DB::table('orders')->whereIn('orders.status', ['Pending Invoiced', 'Invoiced', 'Stock Out', 'Customer Confirm', 'Request to Return', 'Paid', 'Return', 'Lost', 'Completed', 'Delivered', 'Customer On Hold'])
                ->whereYear('orders.created_at',  '>=', Carbon::now()->year)->whereMonth('orders.created_at',  '>=', Carbon::now()->month)
                ->join('orderproducts', 'orders.id', '=', 'orderproducts.order_id')
                ->select('orders.status', 'orders.orderDate', 'orderproducts.*', DB::raw('SUM(quantity) as total_amount'))
                ->groupBy('orderproducts.product_id')->orderBy('total_amount', 'desc')->get();
            $response['status'] = 'success';
            return json_encode($response);
        } else {
            $response['orders'] = DB::table('orders')->whereIn('orders.status', ['Pending Invoiced', 'Invoiced', 'Stock Out', 'Customer Confirm', 'Request to Return', 'Paid', 'Return', 'Lost', 'Completed', 'Delivered', 'Customer On Hold'])
                ->whereYear('orders.created_at',  '>=', Carbon::now()->year)
                ->join('orderproducts', 'orders.id', '=', 'orderproducts.order_id')
                ->select('orders.status', 'orders.orderDate', 'orderproducts.*', DB::raw('SUM(quantity) as total_amount'))
                ->groupBy('orderproducts.product_id')->orderBy('total_amount', 'desc')->get();
            $response['status'] = 'success';
            return json_encode($response);
        }
    }

    //recent sell

    public function recentsellpeoduct($id)
    {
        if ($id == 0) {
            $response['title'] = '/Today';
            $response['orders'] = Order::with(['orderproducts' => function ($query) {
                $query->select('id', 'order_id', 'productName');
            }, 'customers' => function ($query) {
                $query->select('id', 'order_id', 'customerName');
            }])->whereIn('orders.status', ['Pending Invoiced', 'Invoiced', 'Stock Out', 'Customer Confirm', 'Request to Return', 'Paid', 'Return', 'Lost', 'Completed', 'Delivered', 'Customer On Hold'])
                ->where('updated_at',  '>=', Carbon::today()->format('y-m-d'))->select('id', 'invoiceID', 'subTotal', 'status')->get();
            $response['status'] = 'success';
            return json_encode($response);
        } else if ($id == 1) {
            $response['title'] = '/This Month';
            $response['orders'] = Order::with(['orderproducts' => function ($query) {
                $query->select('id', 'order_id', 'productName');
            }, 'customers' => function ($query) {
                $query->select('id', 'order_id', 'customerName');
            }])->whereIn('orders.status', ['Pending Invoiced', 'Invoiced', 'Stock Out', 'Customer Confirm', 'Request to Return', 'Paid', 'Return', 'Lost', 'Completed', 'Delivered', 'Customer On Hold'])
                ->whereYear('updated_at',  '>=', Carbon::now()->year)->whereMonth('updated_at',  '>=', Carbon::now()->month)->select('id', 'invoiceID', 'subTotal', 'status')->get();
            $response['status'] = 'success';
            return json_encode($response);
        } else {
            $response['title'] = '/This Year';
            $response['orders'] = Order::with(['orderproducts' => function ($query) {
                $query->select('id', 'order_id', 'productName');
            }, 'customers' => function ($query) {
                $query->select('id', 'order_id', 'customerName');
            }])->whereIn('orders.status', ['Pending Invoiced', 'Invoiced', 'Stock Out', 'Customer Confirm', 'Request to Return', 'Paid', 'Return', 'Lost', 'Completed', 'Delivered', 'Customer On Hold'])
                ->whereYear('updated_at',  '>=', Carbon::now()->year)->select('id', 'invoiceID', 'subTotal', 'status')->get();
            $response['status'] = 'success';
            return json_encode($response);
        }
    }



    // Get Payment Type
    public function paymenttype(Request $request)
    {
        if (isset($request['q'])) {
            $paymentTypes = Paymenttype::query()->where([
                ['paymentTypeName', 'like', '%' . $request['q'] . '%'],
                ['status', 'Active']
            ])->get();
        } else {
            $paymentTypes = PaymentType::query()->where('status', 'Active')->get();
        }
        $paymentType = array();
        foreach ($paymentTypes as $item) {
            $paymentType[] = array(
                "id" => $item['id'],
                "text" => $item['paymentTypeName']
            );
        }
        return json_encode($paymentType);
    }

    //Payment_ID
    public function payment_id(Request $request)
    {
        if (isset($request['q'])) {
            $users = Payment::query()->where('name', 'like', '%' . $request['q'] . '%')->get();
        } else {
            $users = Payment::all();
        }
        $user = array();
        foreach ($users as $item) {
            $user[] = array(
                "id" => $item['id'],
                "text" => $item['paymentNumber']
            );
        }
        return json_encode($user);
    }

    //payment number
    public function paymentnumber(Request $request)
    {
        if (isset($request['q']) && $request['paymentTypeID']) {
            $payments = Payment::query()->where([
                ['paymentNumber', 'like', '%' . $request['q'] . '%'],
                ['status', 'like', 'Active'],
                ['payment_type_id', '=', $request['paymentTypeID']]
            ])->get();
        } else {
            $payments = Payment::query()->where([
                ['status', 'like', 'Active'],
                ['payment_type_id', '=', $request['paymentTypeID']]
            ])->get();
        }
        $payment = array();
        foreach ($payments as $item) {
            $payment[] = array(
                "id" => $item['id'],
                "text" => $item['paymentNumber']
            );
        }
        return json_encode($payment);
    }




    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $orders = DB::table('orders')
            ->select('orders.*', 'customers.customerName', 'customers.customerPhone', 'customers.customerAddress', 'couriers.courierName', 'cities.cityName', 'zones.zoneName', 'admins.name',  'paymenttypes.paymentTypeName', 'payments.paymentNumber')
            ->leftJoin('customers', 'orders.id', '=', 'customers.order_id')
            ->leftJoin('couriers', 'orders.courier_id', '=', 'couriers.id')
            ->leftJoin('paymenttypes', 'orders.payment_type_id', '=', 'paymenttypes.id')
            ->leftJoin('payments', 'orders.payment_id', '=', 'payments.id')
            ->leftJoin('cities', 'orders.city_id', '=', 'cities.id')
            ->leftJoin('zones', 'orders.zone_id', '=', 'zones.id')
            ->leftJoin('admins', 'orders.admin_id', '=', 'admins.id')
            ->where('orders.id', '=', $id)
            ->first();

        if (!$orders) {
            return response('Order not found.', 404);
        }

        $products = DB::table('orderproducts')->where('order_id', '=', $id)->get();
        $orders->products = $products;
        $orders->id = $id;

        // Safety checks for missing columns
        if (!isset($orders->trackingLink)) $orders->trackingLink = null;
        if (!isset($orders->cancel_comment)) $orders->cancel_comment = null;
        if (!isset($orders->order_bonus)) $orders->order_bonus = 0;
        if (!isset($orders->parcel_id)) $orders->parcel_id = null;

        return view('admin.content.order.edit')->with('order', $orders);
    }

    //product
    public function admproduct(Request $request)
    {
        if (isset($request['q'])) {
            $products = Product::query()->where('ProductName', 'like', '%' . $request['q'] . '%')->get();
        } else {
            $products = Product::all();
        }
        $product = array();
        foreach ($products as $item) {
            if (App::environment('local')) {
                $item['ProductImage'] = url($item['ProductImage']);
            } else {
                $item['ProductImage'] = url($item['ProductImage']);
            }
            $product[] = array(
                "id" => $item['id'],
                "text" => $item['ProductName'],
                "image" => $item['ProductImage'],
                "productCode" => $item['ProductSku'],
                "productPrice" => $item['ProductResellerPrice']
            );
        }
        $data['data'] = $product;
        return json_encode($data);
        die();
    }

    //old orders
    public function previous_orders(Request $request)
    {
        $order_id = $request['id'];
        $customer = Customer::query()->where('order_id', '=', $order_id)->get()->first();

        if (!$customer) {
            return json_encode(['data' => []]);
        }

        $orders = DB::table('orders')
            ->select('orders.*', 'customers.*', 'users.name')
            ->leftJoin('customers', 'orders.id', '=', 'customers.order_id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where([
                ['customers.order_id', '!=', $order_id],
                ['customers.customerPhone', $customer->customerPhone]
            ])->get();

        $order['data'] = $orders->map(function ($order) {
            $products = DB::table('orderproducts')->select('orderproducts.*')->where('order_id', '=', $order->id)->get();
            $orderProducts = '';
            foreach ($products as $product) {
                $orderProducts = $orderProducts . $product->quantity . ' x ' . $product->productName . '<br>';
            }
            $order->products = rtrim($orderProducts, '<br>');
            return $order;
        });
        return json_encode($order);
    }

    //assign user

    public function assignuser(Request $request)
    {
        $user_id = $request['user_id'];
        $ids = $request['ids'];
        if ($ids) {
            foreach ($ids as $id) {
                $order = Order::find($id);
                $order->admin_id = $user_id;
                $order->save();
                $comment = new Comment();
                $user = Admin::find($user_id);
                $comment->order_id = $id;
                $comment->comment = Auth::guard('admin')->user()->name . ' Successfully Assign #SS00' . $id . ' Order to ' . $user->name;
                $comment->admin_id = Auth::guard('admin')->user()->id;
                $comment->save();
            }
            $response['status'] = 'success';
            $response['message'] = 'Successfully Assign User to this Order';
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Assign User to this Order';
        }
        return json_encode($response);
    }


    //change status by checkbox
    public function statusUpdateByCheckbox(Request $request)
    {

        $status = $request['status'];
        $ids = $request['orders_id'];

        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();

        if (false && $admin->hasRole('Shop')) {
            if ($status == 'Delivered') {
                $response['status'] = 'failed';
                $response['message'] = 'You do not have permission to update status in delivered';
                return json_encode($response);
            }
        }

        if ($ids) {
            foreach ($ids as $id) {
                $order = Order::find($id);
                $customer = Customer::where('order_id', $order->id)->first();
                if ($order->status != 'Canceled' && $status == 'Canceled') {
                    $user = User::where('id', $order->user_id)->first();
                    $user->account_balance = $user->account_balance + $order->paymentAmount;
                    $user->update();
                    $comment = new Comment();
                    $comment->order_id = $id;
                    $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Canceled.Please contact support';
                    $comment->admin_id = Auth::guard('admin')->user()->id;
                    $comment->user_id = $order->user_id;
                    $comment->status = 1;
                    $comment->type = 'Canceled';
                    $comment->save();
                }

                if ($order->status == 'Canceled' && $status != 'Canceled') {
                    $user = User::where('id', $order->user_id)->first();
                    $user->account_balance = $user->account_balance - $order->deliveryCharge;
                    $user->update();
                    $comment = new Comment();
                    $comment->order_id = $id;
                    $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Reassign for delivery';
                    $comment->admin_id = Auth::guard('admin')->user()->id;
                    $comment->user_id = $order->user_id;
                    $comment->status = 1;
                    $comment->type = 'Reassign';
                    $comment->save();
                }

                if ($order->status == 'Delivered') {
                    if ($request['status'] == 'Delivered') {
                    } else {
                        // Reverse delivery credits (status moving away from Delivered)
                        app(\App\Services\OrderDeliveryService::class)->reverseDelivery($order);
                    }
                } else {
                    if ($order->status != 'Delivered' && $request['status'] == 'Delivered') {
                        $comment = new Comment();
                        $comment->order_id = $id;
                        $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Delivered Successfully';
                        $comment->admin_id = Auth::guard('admin')->user()->id;
                        $comment->user_id = $order->user_id;
                        $comment->status = 1;
                        $comment->type = 'Delivered';
                        $comment->save();

                        $order->deliveryDate = date('Y-m-d');

                        // Use centralized delivery service for all financial operations
                        app(\App\Services\OrderDeliveryService::class)->markDelivered($order);
                    }
                }

                if ($status == 'Return') {
                    $comment = new Comment();
                    $comment->order_id = $id;
                    $comment->comment = 'Order ID : ' . $order->invoiceID . ', Customer Name : ' . $customer->customerName . ' is Return The Parcel';
                    $comment->admin_id = Auth::guard('admin')->user()->id;
                    $comment->user_id = $order->user_id;
                    $comment->status = 1;
                    $comment->type = 'Return';
                    $comment->save();
                    $order->completeDate = date('Y-m-d');
                    $orderProducts = Orderproduct::query()->where('order_id', '=', $order->id)->get();
                    foreach ($orderProducts as $orderProduct) {
                        $stock = Stock::query()->where('product_id', '=', $orderProduct->product_id)->first();
                        $stock->stock = $stock->stock + $orderProduct->quantity;
                        $stock->save();
                    }
                }



                $order->status = $status;
                $order->save();
                $comment = new Comment();
                $comment->order_id = $id;
                $comment->comment = Auth::guard('admin')->user()->name . ' Successfully Update #SS00' . $id . ' Order status to ' . $status;
                $comment->admin_id = Auth::guard('admin')->user()->id;
                $comment->status = 1;
                $comment->save();
            }
            $response['status'] = 'success';
            $response['message'] = Auth::guard('admin')->user()->name . ' Successfully Update #SS00' . $id . ' Order status to ' . $status;
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to change status of this Order';
        }
        return json_encode($response);
    }
    //couriers
    public function couriers(Request $request)
    {
        if (isset($request['q'])) {
            $couriers = Courier::where([
                ['courierName', 'like', '%' . $request['q'] . '%'],
                ['status', 'like', 'Active']
            ])->get();
        } else {
            $couriers = Courier::where('status', 'Active')->get();
        }
        $courier = array();
        foreach ($couriers as $item) {
            $courier[] = array(
                "id" => $item['id'],
                "text" => $item['courierName']
            );
        }
        return json_encode($courier);
    }

    // Get City
    public function city(Request $request)
    {
        if (isset($request['q']) && $request['courierID']) {
            $cites = City::query()->where([
                ['cityName', 'like', '%' . $request['q'] . '%'],
                ['status', 'like', 'Active'],
                ['courier_id', '=', $request['courierID']]
            ])->get();
        } else {
            $cites = City::query()->where([
                ['status', 'Active'],
                ['courier_id', '=', $request['courierID']]
            ])->get();
        }
        $city = array();
        foreach ($cites as $item) {
            $city[] = array(
                "id" => $item['id'],
                "text" => $item['cityName']
            );
        }
        return json_encode($city);
    }

    // Get Zone
    public function zone(Request $request)
    {
        if (isset($request['q'])) {
            $zones = Zone::query()->where([
                ['zoneName', 'like', '%' . $request['q'] . '%'],
                ['courier_id', '=', $request['courierID']],
                ['status', 'Active'],
                ['city_id', 'like',  $request['cityID']]
            ])->get();
        } else {
            $zones = Zone::query()->where([
                ['courier_id', 'like',  $request['courierID']],
                ['city_id', 'like',  $request['cityID']],
                ['status', 'Active']
            ])->get();
        }
        $zone = array();
        foreach ($zones as $item) {
            $zone[] = array(
                "id" => $item['id'],
                "text" => $item['zoneName']
            );
        }
        return json_encode($zone);
    }



    //comments get
    public function getComments(Request $request)
    {
        $order_id = $request['id'];
        $comment = Comment::where('order_id',  $order_id)->latest()->get();

        $comment['data'] = $comment->map(function ($comment) {
            $admin = DB::table('admins')->select('admins.name')->where('id', '=', $comment->admin_id)->get()->first();
            $comment->name = $admin->name;
            $comment->date = $comment->created_at ? $comment->created_at->diffForHumans() : '';
            return $comment;
        });
        return json_encode($comment);
    }

    public function updateComments(Request $request)
    {
        $id = $request['id'];
        $note = $request['comment'];
        $notification = new Comment();
        $notification->order_id = $id;
        $notification->comment = $note;
        $notification->admin_id = Auth::guard('admin')->user()->id;
        $request = $notification->save();

        if ($request) {
            $response['status'] = 'success';
            $response['message'] = 'Note Successfully Added To This Order';
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Update Order note';
        }
        return json_encode($response);
        die();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        $order->store_id = $request['data']['storeID'];
        $order->trackingLink = $request['data']['trackingLink'];
        if (isset($request['data']['parcelID'])) {
            $order->parcel_id = $request['data']['parcelID'];
        }
        $order->subTotal = $request['data']['total'];
        $oldAmount = $order->paymentAmount;
        $newAmount = $request['data']['paymentAmount'];
        $order->memo = $request['data']['memo'];
        if (isset($request['data']['customerNote'])) {
            $order->customerNote = $request['data']['customerNote'];
        }

        if (isset($request['data']['cancel_comment'])) {
            $order->cancel_comment = $request['data']['cancel_comment'];
        } else {
            $order->cancel_comment = "";
        }

        $order->deliveryCharge = $request['data']['deliveryCharge'];
        $order->discountCharge = $request['data']['discountCharge'];
        $order->payment_type_id = $request['data']['paymentTypeID'];
        $order->payment_id = $request['data']['paymentID'];
        $order->paymentAmount = $request['data']['paymentAmount'];
        $order->paymentAgentNumber = $request['data']['paymentAgentNumber'];
        $order->orderDate = $request['data']['orderDate'];
        if (!empty($request['data']['deliveryDate'])) {
            $order->deliveryDate = $request['data']['deliveryDate'];
        }
        if (!empty($request['data']['completeDate'])) {
            $order->completeDate = $request['data']['completeDate'];
        }
        $order->courier_id = $request['data']['courierID'];
        $order->city_id = $request['data']['cityID'];
        $order->zone_id = $request['data']['zoneID'];
        $products = $request['data']['products'];

        $buy = 0;
        $bonus = 0;
        foreach ($products as $product) {
            $buy += Product::where('id', $product['productID'])->first()->ProductResellerPrice * $product['productQuantity'];
            $bonus += Product::where('id', $product['productID'])->first()->reseller_bonus;
        }
        $order->profit = ($request['data']['total'] + $request['data']['paymentAmount']) - ($request['data']['deliveryCharge'] + $buy);
        $order->order_bonus = $bonus;

        $result = $order->update();
        if ($result) {
            $customer = Customer::where('order_id', '=', $id)->first();
            $customer->customerName = $request['data']['customerName'];
            $customer->customerPhone = $request['data']['customerPhone'];
            $customer->customerAddress = $request['data']['customerAddress'];
            $customer->update();
            Orderproduct::where('order_id', '=', $id)->delete();
            foreach ($products as $product) {
                $orderProducts = new Orderproduct();
                $orderProducts->order_id = $id;
                $orderProducts->product_id = $product['productID'];
                $orderProducts->productCode = $product['productCode'];
                $orderProducts->productName = $product['productName'];
                $orderProducts->color = $product['productColor'];
                $orderProducts->size = $product['productSize'];
                $orderProducts->quantity = $product['productQuantity'];
                $orderProducts->productPrice = $product['productPrice'];
                $orderProducts->save();
            }
            $comment = new Comment();
            $comment->order_id = $id;
            $comment->comment = Auth::guard('admin')->user()->name . ' Successfully Update Info of #SS00' . $id;
            $comment->admin_id = Auth::guard('admin')->user()->id;
            $comment->save();

            $paymentComplete = Paymentcomplete::where('order_id', $order->id)->first();
            if ($paymentComplete) {
                $paymentComplete->payment_type_id = $request['data']['paymentTypeID'];
                $paymentComplete->payment_id = $request['data']['paymentID'];
                if ($newAmount != $oldAmount) {
                    $paymentComplete->amount = $request['data']['paymentAmount'];
                    $paymentComplete->date = date('Y-m-d');
                }
                $paymentComplete->trid = $request['data']['paymentAgentNumber'];
                $paymentComplete->userID = Auth::guard('admin')->user()->id;
                $paymentComplete->update();
            } else {
                $paymentComplete = new Paymentcomplete();
                $paymentComplete->order_id = $order->id;
                $paymentComplete->payment_type_id = $request['data']['paymentTypeID'];
                $paymentComplete->payment_id = $request['data']['paymentID'];
                $paymentComplete->amount = $request['data']['paymentAmount'];
                $paymentComplete->trid = $request['data']['paymentAgentNumber'];
                $paymentComplete->date = date('Y-m-d');
                $paymentComplete->userID = Auth::guard('admin')->user()->id;
                $paymentComplete->save();
            }
            $response['status'] = 'success';
            $response['message'] = Auth::guard('admin')->user()->name . ' Successfully Update Info of #SS00' . $id;;
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Update Order';
        }
        return json_encode($response);
    }

    // Delete All Orders
    public function delete_selected_order(Request $request, $id)
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();

        if ($id) {

            if ($admin->hasrole('Superadmin')) {
                $result = Order::find($id);
                if ($result) {
                    $result->delete();
                    Customer::query()->where('order_id', '=', $id)->delete();
                    Orderproduct::query()->where('order_id', '=', $id)->delete();
                    Comment::query()->where('order_id', '=', $id)->delete();
                }

                $response['status'] = 'success';
                $response['message'] = 'Successfully Delete Order';
            } else {
                $response['status'] = 'failed';
                $response['message'] = 'You dont have permission to delete order';
            }
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Delete Order';
        }
        return json_encode($response);
    }


    //unique id
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


    //Invoice View
    public function storeInvoice(Request $request)
    {
        $ids = serialize($request['orders_id']);
        $invoice = new Invoice();
        $invoice->order_id = $ids;
        $result = $invoice->save();
        if ($result) {
            $response['status'] = 'success';
            $response['link'] = url('admin_order/invoice/') . '/' . $invoice->id;
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Add Order';
        }
        return json_encode($response);
        die();
    }

    public function viewInvoice($id)
    {
        $invoice = Invoice::find($id);
        return view('admin.content.order.printinvoice', ['invoice' => $invoice]);
    }



    //send message

    public function sendmessage(Request $request)
    {

        $customerPhone = $request['customerPhone'];
        $customerName = $request['customerName'];
        $invoiceID = $request['invoiceID'];
        $paymentTypeID = $request['paymentTypeID'];
        $orderID = $request['orderID'];
        $ido = $request['storeID'];
        $store = Store::find($ido);

        $storeURL = $store->storeUrl;
        $paymentID = $request['paymentID'];

        $sendstatus = Http::get('https://api.mobireach.com.bd/SendTextMessage?Username=danpitee&Password=Dhaka@5599&From=8801639222111&To=88' . $customerPhone . '&Message=Dear,Customer Please pay your Delivery Charge via ' . $paymentTypeID . ' Personal, ' . $paymentTypeID . ': ' . $paymentID . ' Reference:' . $invoiceID . '.' . $storeURL . '');

        if ($sendstatus) {
            $comment = new Comment();
            $comment->order_id = $orderID;
            $comment->comment = Auth::guard('admin')->user()->name . ' Send Sms for ' . $paymentTypeID . ' payment on ' . $paymentID . ' For ' . $orderID . ' Order';
            $comment->admin_id = Auth::guard('admin')->user()->id;
            $comment->save();
            $response['status'] = 'success';
            $response['message'] = 'Successfully Send SMS';
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Send SMS';
        }

        return json_encode($response);
        die();
    }

    public function sendwebsite(Request $request)
    {

        $customerPhone = $request['customerPhone'];
        $websiteLink = $request['websiteLink'];
        $orderID = $request['orderID'];

        $sendstatus = Http::get('https://api.mobireach.com.bd/SendTextMessage?Username=danpitee&Password=Dhaka@5599&From=8801639222111&To=88' . $customerPhone . '&Message=Please visit this link ' . $websiteLink . ' to see our products');

        if ($sendstatus) {
            $comment = new Comment();
            $comment->order_id = $orderID;
            $comment->comment = Auth::guard('admin')->user()->name . ' Send Website link to SS00' . $orderID . ' ';
            $comment->admin_id = Auth::guard('admin')->user()->id;
            $comment->save();
            $response['status'] = 'success';
            $response['message'] = 'Website link Send Successfully';
        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Unsuccessful to Send SMS';
        }

        return json_encode($response);
        die();
    }
}

