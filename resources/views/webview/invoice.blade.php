 
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
    @yield('meta')

    {{-- Header link includes --}}
    @include('frontend.linkincludes.css') 

    <style>
        .vendor-item {
            padding: 6px;
            border: 1px solid;
        }

        /* mobile menu */
        body.modal-open {
            height: 100vh;
            overflow-y: hidden;
        }

        .sidenav {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 100;
            top: 0px;
            left: 0;
            /* background-color: #111; */
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }

        .sidenav a {
            padding: 5px 5px 5px 20px;
            text-decoration: none;
            font-size: 25px;
            color: #818181;
            display: block;
            transition: 0.3s;
        }

        .sidenav a:hover {
            color: #f1f1f1;
        }

        .sidenav .closebtn {
            position: absolute;
            top: 0;
            right: 6px;
            font-size: 36px;
            /*margin-left: 50px;*/
            color: #fff;
        }

        @media screen and (max-width: 450px) {
            .sidenav {
                padding-top: 10px;
            }

            .sidenav a {
                font-size: 16px;
            }
        }
    </style>
</head>

<body> 
    
    <!-- /.breadcrumb -->
<div class="body-content outer-top-xs">

    <section class="mt-1 mb-3">
        <div class="container">

            @if ($orders == 'Nothing')
            @else
                @if (isset($orders))
                    {{-- track list --}}
                    <div class="card mt-4">
                        <div class="card-header py-2 px-3 heading-6 strong-600 clearfix">
                            <div class="float-center" style="color: red;text-align:center"> <b>INVOICE OF {{ $orders->invoiceID }}</b> </div>
                        </div> 
                        
                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-lg-6">
                                    <table class="details-table table">
                                        <tbody>
                                            <tr>
                                                <td class="w-50 strong-600">Order ID:</td>
                                                <td>{{ $orders->invoiceID }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Customer:</td>
                                                <td>{{ $orders->customers->customerName }}</td>
                                            </tr> 
                                            <tr>
                                                <td class="w-50 strong-600">Phone:</td>
                                                <td>*******{{ substr ($orders->customers->customerPhone, -4) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Shipping address:</td>
                                                <td>{{ $orders->customers->customerAddress }},@if (isset($orders->zones))
                                                        {{ $orders->zones->zoneName }},
                                                    @else
                                                        @endif @if (isset($orders->cities))
                                                            {{ $orders->cities->cityName }},
                                                        @else
                                                        @endif
                                                </td>
                                            </tr>
                                             <tr>
                                                <td class="w-50 strong-600">Shipping company:</td>
                                                <td>
                                                    @if (isset($orders->couriers))
                                                        {{ $orders->couriers->courierName }}
                                                    @else
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-lg-6">
                                    <table class="details-table table">
                                        <tbody>
                                            <tr>
                                                <td class="w-50 strong-600">Order date:</td>
                                                <td>{{ $orders->created_at->format('Y-m-d') }} ,
                                                    {{ date('h:i A', strtotime($orders->created_at)) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Total order amount:</td>
                                                <td>৳ {{ ($orders->subTotal+$orders->paymentAmount )-$orders->deliveryCharge}} + <span style="color: red">( Charge : {{ $orders->deliveryCharge}} ৳)</span> </td>
                                            </tr>
                                           
                                            <tr>
                                                <td class="w-50 strong-600">Payment method:</td>
                                                <td>
                                                    @if ($orders->Payment == 'C-O-D')
                                                        Cash On Delivery
                                                    @else
                                                        Online Payment
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Paid:</td>
                                                <td>
                                                    @if($orders->paymentAmount>0)
                                                        {{$orders->paymentAmount}} TK
                                                    @else
                                                        00 TK
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Due:</td>
                                                <td>
                                                    {{$orders->subTotal}} TK
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-4">
                        
                        <div class="card-body p-4">
                            <div class="col-12">
                                <table class="details-table table">
                                    <tbody>
                                        @forelse ($orders->orderproducts as $products)
                                            <tr>
                                                <td class="w-50 strong-600">Product Name:</td>
                                                <td>{{ $products->productName }} &nbsp; <span style="color: red">(
                                                        {{ $products->quantity }}
                                                        pics )</span>
                                                </td>
                                            </tr>
                                        @empty
                                        @endforelse
                                        <tr>
                                            <td class="w-50 strong-600">Completed By:</td>
                                            <td>{{ $orders->admins->name }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                    </div>
                @else
                    <div class="card mt-4">
                        <div class="card-header py-2 px-3 heading-6 strong-600 clearfix">
                            <div class="float-left" style="color: red;text-align:center">No Records Found.Please call
                                our customer care or use Live Chat
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>

</div>

<style>
    .process-steps {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .process-steps li {
        width: 25%;
        float: left;
        text-align: center;
        position: relative;
    }

    .process-steps li .icon {
        height: 30px;
        width: 30px;
        margin: auto;
        background: #fff;
        border-radius: 50%;
        line-height: 30px;
        font-size: 14px;
        font-weight: 700;
        color: #adadad;
        position: relative;
    }

    .process-steps li .title {
        font-weight: 600;
        font-size: 13px;
        color: #777;
        margin-top: 8px;
        margin-bottom: 0;
    }

    .process-steps li+li:after {
        position: absolute;
        content: "";
        height: 3px;
        width: calc(100% - 30px);
        background: #fff;
        top: 14px;
        z-index: 0;
        right: calc(50% + 15px);
    }

    .breadcrumb {
        padding: 5px 0;
        border-bottom: 1px solid #e9e9e9;
        background-color: #fafafa;
    }

    .search-area .search-button {
        border-radius: 0px 3px 3px 0px;
        display: inline-block;
        float: left;
        margin: 0px;
        padding: 5px 15px 6px;
        text-align: center;
        background-color: #e62e04;
        border: 1px solid #e62e04;
    }

    .search-area .search-button:after {
        color: #fff;
        content: "\f002";
        font-family: fontawesome;
        font-size: 16px;
        line-height: 9px;
        vertical-align: middle;
    }
</style>

    {{-- //footer link (js) include --}}
    @include('frontend.linkincludes.js')

    @yield('subjs')
 
</body>

</html>
