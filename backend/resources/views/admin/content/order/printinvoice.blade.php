<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invoice</title>
    <link href="{{ asset('public/admin/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('public/admin/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('public/admin/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        * {
            margin: 0px;
            padding: 0px;
        }

        table {
            width: 100%;
        }

        table,
        th,
        td {
            border: 1px solid gray;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }

        table.table-with-info tr:nth-child(even) {
            background-color: #eee;
        }

        table.table-with-info tr:nth-child(odd) {
            background-color: #fff;
        }

        table.table-with-info th {
            background-color: black;
            color: white;
        }

        hr {
            border-top: 1px dashed red;
        }

        table.table-with-info,
        table.table-with-info td,
        table.table-with-info th {
            border: 0px solid black;
        }

        @media print {

            .section {
                display: flex;
                flex-direction: column;
                width: 100%;
                height: 100vh;
                justify-content: space-between;
            }
        }
    </style>
</head>

<body>
    <?php
    use Illuminate\Support\Facades\DB;
    $orderIDs = unserialize($invoice->order_id); ?>


    <?php $count = 1; foreach ($orderIDs as $orderID) {


    $order  = DB::table('orders')
        ->select('orders.*', 'customers.customerName', 'customers.customerPhone', 'customers.customerAddress', 'couriers.courierName', 'cities.cityName', 'zones.zoneName', 'users.name', 'paymenttypes.paymentTypeName', 'payments.paymentNumber')
        ->leftJoin('customers', 'orders.id', '=', 'customers.order_id')
        ->leftJoin('couriers', 'orders.courier_id', '=', 'couriers.id')
        ->leftJoin('paymenttypes', 'orders.payment_type_id', '=', 'paymenttypes.id')
        ->leftJoin('payments', 'orders.payment_id', '=', 'payments.id')
        ->leftJoin('cities', 'orders.city_id', '=', 'cities.id')
        ->leftJoin('zones', 'orders.zone_id', '=', 'zones.id')
        ->leftJoin('users', 'orders.user_id', '=', 'users.id')
        ->where('orders.id', '=', $orderID)->get()->first();
    if($count == 1) {
        echo '<div class="section">';
        $last = true;
    }
     ?>
    <div class="div-section" style="    font-size: 17px;">
        <table class="table-with-info table-striped" cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 25%;">
                    @if(App\Models\User::where('id',$order->user_id)->first()->profile)
                        <img src="{{asset(App\Models\User::where('id',$order->user_id)->first()->profile)}}" style="width:200px;margin-bottom:10px;height:60px">
                    @endif

                    <h4>CUSTOMER INFO</h4>
                    {{ $order->customerName }} <br>
                    {{ $order->customerPhone }}<br>
                    @if ($order->courierName == 'Sa Paribahan' || $order->courierName == 'Sundorban')

                        {{ $order->courierName }} @if ($order->cityName)
                            >> {{ $order->cityName }}
                            @endif @if ($order->zoneName)
                                >> {{ $order->zoneName }}
                            @endif
                        @else
                            {{ $order->customerAddress }} <br>
                            {{ $order->courierName }} @if ($order->cityName)
                                >> {{ $order->cityName }}
                                @endif @if ($order->zoneName)
                                    >> {{ $order->zoneName }}
                                @endif
                            @endif
                </td>
                <td style="text-align:center">
                    <strong style="font-size:36px">
                        {{App\Models\User::where('id',$order->user_id)->first()->shop_name}}
                    </strong>
                    <br>
                    <strong>
                        Seller ID: {{App\Models\User::where('id',$order->user_id)->first()->my_referral_code}}<br>
                        Mobile : {{ App\Models\Basicinfo::first()->phone_one}}
                    </strong>
                </td>
                <td style="width: 30%;text-align:right">
                    <h4 class="mb-2">INVOICE </h4>
                    <?php
                    echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($order->invoiceID, 'C39') . '" alt="barcode"   />';
                    ?>
                    <h4>Invoice #{{ $order->invoiceID }}</h4>
                    Order Date : {{ $order->orderDate }}<br>
                    @if ($order->courierName == 'Sa Paribahan' || $order->courierName == 'Sundorban')
                        Payment Method : Courier Condition
                    @else
                        Payment Method : Cash On Delivery
                    @endif

                </td>

            </tr>
        </table>
        <table class="">
            <tr>
                <th style="width: 60%">Product</th>
                <th style="width: 20%">Quantity</th>
                <th style="width: 20%">Price</th>
            </tr>
            <?php
            $products = DB::table('orderproducts')->where('order_id', '=', $orderID)->get();
            foreach ($products as $product) { ?>
            <tr>
                <td>{{ $product->productName }} @if ($product->color && $product->size)
                        (Colour: {{ $product->color }} , Size: {{ $product->size }})
                    @elseif($product->size)
                        (Size: {{ $product->size }})
                    @elseif($product->color)
                        (Size: {{ $product->color }})
                    @else
                    @endif
                </td>
                <td>{{ $product->quantity }}</td>
                <td>{{ $product->productPrice }} Tk</td>
            </tr>
            <?php } ?>
            <tfoot>
                <tr>
                    <td colspan="1" style="border: none;"></td>
                    <th>Delivery Charge: </th>
                    <td>{{ $order->deliveryCharge }} Tk</td>
                </tr>
                <tr>
                    <td colspan="1" style="border: none;"></td>
                    <th>Sub Total : </th>
                    <td>{{ $order->subTotal+$order->paymentAmount }} Tk</td>
                </tr>
                <tr>
                    <td colspan="1" style="border: none;"></td>
                    <th>Paid : </th>
                    <td>{{ $order->paymentAmount }} Tk</td>
                </tr>
                <tr>
                    <td colspan="1" style="border: none;"></td>
                    <th>Due : </th>
                    <td>{{ $order->subTotal }} Tk</td>
                </tr>

        </table>
        <div style=" display: flex; flex-direction: row; justify-content: space-between; ">
            <p>{{ App\Models\Basicinfo::first()->invoice_footer }} and purchase date ({{ date('Y-m-d') }}). </p>
            <p>Order Recived By : @if(isset($order->store_id)) VID{{App\Models\Admin::where('id',$order->store_id)->first()->id}} @else @endif</p>
        </div>
    </div>
    <hr>
    <?php
    if($count == 3 ) {
        echo '</div>';
        $count = 1;
    }else{
        $count++;
    }
    } ?>
    </div>

    <script src="{{ asset('public/admin/js/jquery.min.js') }}"></script>
    <script src="{{ asset('public/admin/js/vendor.min.js') }}"></script>
    <!-- App js -->
    <script src="{{ asset('public/admin/js/app.min.js') }}"></script>
    <script>
        $(function() {
            window.print();
            window.onfocus = function() {
                window.close();
            }
            window.onafterprint = function() {
                window.close();
            };


        });
    </script>
</body>

</html>
