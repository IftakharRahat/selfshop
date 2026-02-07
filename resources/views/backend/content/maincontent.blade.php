@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }}-Admin
@endsection
<style>
    .rounded-leftc{
        color: black;
        margin-left: -17px;
        background: #e8e8e8;
        width: 50px;
        border-radius: 0px 30px 30px 0px;
    }
    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #11186b;
        background-clip: border-box;
        border: 1px solid #fff;
        border-radius: 5px;
        box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
    }

    .text-dark {
        color: #000 !important;
    }

    .text-muted {
        color: #000 !important;
    }
</style>
<div class="px-4 pt-4 container-fluid">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <?php
    use App\Models\Comment;
    use App\Models\Admin;
    $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
    $users = Admin::whereHas('roles', function ($q) {
        $q->where('name', 'user');
    })->count();
    $ordercount = DB::table('orders')->count();
    $orderamount = DB::table('orders')
        ->where('status', 'Paid')
        ->sum('subTotal');
    $comments = Comment::latest()
        ->take(100)
        ->get();

    ?>
    @if ($admin->hasRole('user'))
        <div class="row">
            <!-- Revenue Card -->
            <div class="mb-2 col-md-6 col-xl-2">
                @if ($admin->hasRole('user'))
                    <a href="{{ url('/user/order') }}">
                    @else
                        <a href="{{ url('admin_order/orderall') }}">
                @endif
                <div class="p-2 widget-rounded-circle card order">
                    <div class="row">
                        <div class="col-12">
                            <div class="float-left">
                                <h3 class="mt-1 mb-0 text-dark">
                                    <span id="all">0</span>
                                </h3>
                                <p class="mb-1 text-muted text-truncate">All Orders</p>
                            </div>
                        </div>
                    </div> <!-- end row-->
                </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->

            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Processing') }}">
                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="processing" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Processing</p>
                                </div>
                            </div>
                        </div> <!-- end row-->
                    </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->

            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Canceled') }}">
                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="canceled" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Canceled</p>
                                </div>
                            </div>
                        </div> <!-- end row-->
                    </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->

            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Completed') }}">

                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="completed" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Completed</p>
                                </div>
                            </div>
                        </div> <!-- end row-->
                    </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->
            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Processing') }}">
                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="processing" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Processing</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Packageing') }}">
                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="packageing" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Packageing</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Ontheway') }}">
                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="ontheway" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Ontheway</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Delivered') }}">
                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="delivered" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Delivered</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-2 col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Return') }}">
                    <div class="p-2 widget-rounded-circle card order">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="return" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Return</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    @else
        <div class="row">

            <!-- Left side columns -->
            <div class="col-lg-12">
                @if($admin->hasRole('Shop'))
                <div class="mb-4 row">
                    <div class="col-md-12 card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
                        <div class="row">
                            <div class="col-md-3">
                                <div id="chartContainer" style="height: 300px; width: 100%;"></div>
                                <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
                            </div>
                            <div class="col-md-9 ps-4">
                                <h4 style="font-size:16px">Check Date-wise Report</h4>
                                 <div class="d-flex justify-content-between">
                                     <div class="ms-lg-1">
                                        <h4 style="font-size:16px">From</h4>
                                        <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="startDate">
                                     </div>
                                      <div>
                                        <h4 style="font-size:16px">To</h4>
                                        <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="endDate">
                                     </div>
                                 </div>

                                 <div class="mt-4 row">
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/orderall') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="all" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">All</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Pending') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="pending" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Pending</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Canceled') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="canceled" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Canceled</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Confirmed') }}">

                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="confirmed" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Confirmed</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Processing') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="processing" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Processing</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Packageing') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="packageing" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Packageing</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Ontheway') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="ontheway" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Ontheway</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Delivered') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="delivered" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Delivered</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Return') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="return" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Return</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="mb-4 row">
                    <div class="col-md-12 card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
                        <div class="row">
                            <div class="col-md-3">
                                <div id="chartContainer" style="height: 300px; width: 100%;"></div>
                                <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
                            </div>
                            <div class="col-md-9 ps-4">
                                <h4 style="font-size:16px">Check Date-wise Report</h4>
                                 <div class="d-flex justify-content-between">
                                     <div class="ms-lg-1">
                                        <h4 style="font-size:16px">From</h4>
                                        <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="startDate">
                                     </div>
                                      <div>
                                        <h4 style="font-size:16px">To</h4>
                                        <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="endDate">
                                     </div>
                                 </div>

                                 <div class="mt-4 row">
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/orderall') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="all" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">All</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Pending') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="pending" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Pending</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Canceled') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="canceled" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Canceled</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Confirmed') }}">

                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="confirmed" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Confirmed</p>
                                                        </div>
                                                    </div>
                                                </div> <!-- end row-->
                                            </div> <!-- end widget-rounded-circle-->
                                        </a>
                                    </div> <!-- end col-->
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Processing') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="processing" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Processing</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Packageing') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="packageing" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Packageing</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Ontheway') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="ontheway" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Ontheway</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Delivered') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="delivered" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Delivered</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="mb-2 col-md-6 col-xl-3">
                                        <a href="{{ url('admin_order/Return') }}">
                                            <div class="p-2 widget-rounded-circle card-box order" style="background: #fff;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="float-left">
                                                            <h3 class="mt-1 mb-0 text-dark">
                                                                <span id="return" data-plugin="counterup">0</span>
                                                            </h3>
                                                            <p class="mb-1 text-muted text-truncate">Return</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-4 row">
                    <div class="col-md-12 card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
                        <div class="row">

                            <div class="mb-3 col-md-12">
                                <h6>Date filter Profit-Loss System</h6>

                                <div class="d-flex justify-content-between">
                                    <div class="ms-lg-1">
                                        <h4 style="font-size:16px">From</h4>
                                        <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="fromDate">
                                    </div>
                                    <div>
                                        <h4 style="font-size:16px">To</h4>
                                        <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="toDate">
                                    </div>
                                </div>
                            </div>
                            <!-- Revenue Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card revenue-card" style="background:#fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Profit</h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="order">
                                                0
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: black;margin: 0;" id="profit"> 0 </h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Revenue Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card sales-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Reseller Profit<span></span></h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="resellerorder">
                                                0
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: black;margin: 0;" id="resellerprofit"> 0 </h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Sales Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card revenue-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Profit</h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="totalorder">
                                                0
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: black;margin: 0;" id="totalprofit">  {{ \App\Models\Order::where('orderDate',date('Y-m-d'))->get()->sum('subTotal')}}</h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Revenue Card -->
                            <!-- Revenue Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card revenue-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;"> Pending Profit</h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="pendingorder">
                                                0
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: black;margin: 0;" id="pendingprofit">0</h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Revenue Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card sales-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;"> Pending Reseller Profit<span></span></h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="resellerpendingorder">
                                                 0
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: black;margin: 0;" id="resellerpendingprofit"> 0 </h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Sales Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card revenue-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;"> Pending Total Profit</h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="totalpendingorder">
                                                0
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: #6e6e6e;margin: 0;" id="totalpendingprofit"> 0 </h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Revenue Card -->
                        </div>
                    </div>
                </div>

<div class="mb-4 row">
    <div class="col-md-12 card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
        <div class="row">
            <div class="col-md-12">
                <h6>Sales Report</h6>
            </div>
            <!-- Revenue Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card revenue-card" style="background:#fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Sales</h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::where('status','Delivered')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::where('status','Delivered')->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Revenue Card -->

            <!-- Order Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card sales-card" style="background: #fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">This Year Sales<span></span></h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::whereYear('deliveryDate',Carbon\Carbon::now()->year)->where('status','Delivered')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #6e6e6e;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::whereYear('deliveryDate',Carbon\Carbon::now()->year)->where('status','Delivered')->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Sales Card -->
            
            <!-- Revenue Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card revenue-card" style="background: #fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">This Month Sales</h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::whereMonth('deliveryDate', Carbon\Carbon::now()->month)->where('status','Delivered')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <?php
                                        $monthSubTotal = \App\Models\Order::where('status','Delivered')
                                            ->whereMonth('deliveryDate', Carbon\Carbon::now()->month)
                                            ->get()->sum('subTotal');
                                        $monthPaymentAmount = \App\Models\Order::where('status','Delivered')
                                            ->whereMonth('deliveryDate', Carbon\Carbon::now()->month)
                                            ->get()->sum('paymentAmount');
                                        $monthTotal = $monthSubTotal + $monthPaymentAmount;
                                    ?>
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format($monthTotal) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Revenue Card -->
            
            <!-- Customers Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card customers-card" style="background: #fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today's Sales<span></span></h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::where('deliveryDate', date('Y-m-d'))->where('status','Delivered')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::where('status','Delivered')->where('deliveryDate', date('Y-m-d'))->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div><!-- End Customers Card -->
        </div>
    </div>
</div>

                <div class="mb-4 row">
                    <div class="col-md-12 card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Today's Report</h6>
                            </div>
                            <!-- Revenue Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card revenue-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today’s Orders</h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="to">
                                                 {{ \App\Models\Order::where('orderDate',date('Y-m-d'))->get()->count()}}
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: #6e6e6e;margin: 0;" id="toa">  {{ \App\Models\Order::where('orderDate',date('Y-m-d'))->get()->sum('subTotal')}}</h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Revenue Card -->
                            <!-- Revenue Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card revenue-card" style="background:#fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today’s Confirmed</h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="tc">
                                                 {{ \App\Models\Order::where('status','Confirmed')->where('orderDate',date('Y-m-d'))->get()->count()}}
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: #6e6e6e;margin: 0;" id="tca">  {{ \App\Models\Order::where('status','Confirmed')->where('orderDate',date('Y-m-d'))->get()->sum('subTotal')}}</h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Revenue Card -->
                            <!-- Revenue Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card revenue-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today's Ontheway</h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="tod">
                                                 {{ \App\Models\Order::where('status','Ontheway')->where('orderDate',date('Y-m-d'))->get()->count()}}
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: #6e6e6e;margin: 0;" id="toda">  {{ \App\Models\Order::where('status','Ontheway')->where('orderDate',date('Y-m-d'))->get()->sum('subTotal')}}</h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Revenue Card -->
                            <!-- Order Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">
                                <div class="card info-card sales-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today’s Delivered<span></span></h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="td">
                                                 {{ \App\Models\Order::where('status','Delivered')->where('orderDate',date('Y-m-d'))->get()->count()}}
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: #6e6e6e;margin: 0;" id="tda">  {{ \App\Models\Order::where('status','Delivered')->where('orderDate',date('Y-m-d'))->get()->sum('subTotal')}}</h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Sales Card -->
                            <!-- Customers Card -->
                            <div class="mb-2 col-xxl-3 col-md-3">

                                <div class="card info-card customers-card" style="background: #fff;">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today’s Returned <span></span></h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="tr">
                                                 {{ \App\Models\Order::where('status','Return')->where('orderDate',date('Y-m-d'))->get()->count()}}
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: #6e6e6e;margin: 0;" id="tra">  {{ \App\Models\Order::where('status','Return')->where('orderDate',date('Y-m-d'))->get()->sum('subTotal')}}</h6>

                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div><!-- End Customers Card -->

                            <div class="mb-2 col-xxl-3 col-md-3">

                                <div class="card info-card customers-card" style="background: #fff">

                                    <div class="card-body">
                                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today’s Others <span></span></h5>

                                        <div class="d-flex justify-content-between">
                                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center" id="totd">
                                                 0
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                                    style="color: black;font-weight:normal;">
                                                    ৳
                                                </div>
                                                <div class="ps-1">
                                                    <h6 style="color: #6e6e6e;margin: 0;" id="totda">  0</h6>

                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div><!-- End Customers Card -->

                        </div>
                    </div>
                </div>

               <div class="mb-4 row">
    <div class="col-md-12 card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
        <div class="row">
            <div class="col-md-12">
                <h6>Total Report</h6>
            </div>
            <!-- Revenue Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card revenue-card" style="background: #fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Orders</h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Revenue Card -->
            <!-- Revenue Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card revenue-card" style="background:#fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Confirmed</h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::where('status','Confirmed')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::where('status','Confirmed')->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Revenue Card -->
            <!-- Revenue Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card revenue-card" style="background: #fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Ontheway</h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::where('status','Ontheway')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::where('status','Ontheway')->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Revenue Card -->
            <!-- Order Card -->
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card info-card sales-card" style="background: #fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Delivered<span></span></h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::where('status','Delivered')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::where('status','Delivered')->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Sales Card -->
            <!-- Customers Card -->
            <div class="mb-2 col-xxl-3 col-md-3">

                <div class="card info-card customers-card" style="background: #fff;">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Returned <span></span></h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::where('status','Return')->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::where('status','Return')->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div><!-- End Customers Card -->
            <div class="mb-2 col-xxl-3 col-md-3">

                <div class="card info-card customers-card" style="background: #fff">

                    <div class="card-body">
                        <h5 class="text-left card-title" style="color: black;font-weight:normal;">Other Statuses <span></span></h5>

                        <div class="d-flex justify-content-between">
                            <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                {{ number_format(\App\Models\Order::whereIn('status',['Pending','Processing','Packageing','Canceled'])->get()->count()) }}
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                    style="color: black;font-weight:normal;">
                                    ৳
                                </div>
                                <div class="ps-1">
                                    <h6 style="color: #6e6e6e;margin: 0;"> {{ number_format(\App\Models\Order::whereIn('status',['Pending','Processing','Packageing','Canceled'])->get()->sum('subTotal')) }}</h6>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div><!-- End Customers Card -->
        </div>
    </div>
</div>

<div class="mb-4 row">
    <div class="col-md-12 card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
        <div class="row">
            <div class="mb-2 col-xxl-8 col-md-8">
                <div class="row">
                    <div class="col-md-12">
                        <h6>Reseller Report</h6>
                    </div>
                    <!-- Revenue Card -->
                    <div class="mb-2 col-xxl-4 col-md-4">
                        <div class="card info-card revenue-card" style="background: #fff;">

                            <div class="card-body">
                                <h5 class="text-left card-title" style="color: black;font-weight:normal;">Total Resellers</h5>

                                <div class="d-flex justify-content-between">
                                    <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                        {{ number_format(App\Models\User::get()->count()) }}
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                            style="color: black;font-weight:normal;">

                                        </div>
                                        <div class="ps-1">
                                            <h6 style="color: #6e6e6e;margin: 0;">
                                                {{ number_format(App\Models\User::get()->count()) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div><!-- End Revenue Card -->
                    
                    <!-- Revenue Card -->
                    <div class="mb-2 col-xxl-4 col-md-4">
                        <div class="card info-card revenue-card" style="background:#fff;">

                            <div class="card-body">
                                <h5 class="text-left card-title" style="color: black;font-weight:normal;">Paid Resellers</h5>

                                <div class="d-flex justify-content-between">
                                    <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                        {{ number_format(App\Models\User::where('status','Active')->where('membership_status','Paid')->get()->count()) }}
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #6e6e6e;">

                                        </div>
                                        <div class="ps-1">
                                            <h6 style="color: #6e6e6e;margin: 0;">
                                                {{ number_format(App\Models\User::where('status','Active')->where('membership_status','Paid')->get()->count()) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div><!-- End Revenue Card -->
                    
                    <!-- Revenue Card -->
                    <div class="mb-2 col-xxl-4 col-md-4">
                        <div class="card info-card revenue-card" style="background: #fff;">

                            <div class="card-body">
                                <h5 class="text-left card-title" style="color: black;font-weight:normal;">Unpaid Resellers</h5>

                                <div class="d-flex justify-content-between">
                                    <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                        {{ number_format(App\Models\User::where('membership_status','Unpaid')->get()->count()) }}
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #6e6e6e;">

                                        </div>
                                        <div class="ps-1">
                                            <h6 style="color: #6e6e6e;margin: 0;">
                                                {{ number_format(App\Models\User::where('membership_status','Unpaid')->get()->count()) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div><!-- End Revenue Card -->
                    
                    <!-- Order Card -->
                    <div class="mb-2 col-xxl-4 col-md-4">
                        <div class="card info-card sales-card" style="background: #fff;">

                            <div class="card-body">
                                <h5 class="text-left card-title" style="color: black;font-weight:normal;">Banned Resellers<span></span></h5>

                                <div class="d-flex justify-content-between">
                                    <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                        {{ number_format(App\Models\User::where('status','Block')->get()->count()) }}
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #6e6e6e;">

                                        </div>
                                        <div class="ps-1">
                                            <h6 style="color: #6e6e6e;margin: 0;">
                                                {{ number_format(App\Models\User::where('status','Block')->get()->count()) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div><!-- End Sales Card -->
                    
                    <!-- Customers Card -->
                    <div class="mb-2 col-xxl-4 col-md-4">
                        <div class="card info-card customers-card" style="background: #fff;">

                            <div class="card-body">
                                <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today's Registered<span></span></h5>

                                <div class="d-flex justify-content-between">
                                    <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                        {{ number_format(App\Models\User::where('created_at', '>=', Carbon\Carbon::today())->get()->count()) }}
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #6e6e6e;">

                                        </div>
                                        <div class="ps-1">
                                            <h6 style="color: #6e6e6e;margin: 0;">
                                                {{ number_format(App\Models\User::where('created_at', '>=', Carbon\Carbon::today())->get()->count()) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div><!-- End Customers Card -->

                    <!-- Customers Card -->
                    <div class="mb-2 col-xxl-4 col-md-4">
                        <div class="card info-card customers-card" style="background: #fff;">

                            <div class="card-body">
                                <h5 class="text-left card-title" style="color: black;font-weight:normal;">Today's Active<span></span></h5>

                                <div class="d-flex justify-content-between">
                                    <div class="card-icon rounded-leftc d-flex align-items-center justify-content-center">
                                        {{ number_format(App\Models\User::where('active_date', '>=', Carbon\Carbon::today())->get()->count()) }}
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #6e6e6e;">

                                        </div>
                                        <div class="ps-1">
                                            <h6 style="color: #6e6e6e;margin: 0;">
                                                {{ number_format(App\Models\User::where('active_date', '>=', Carbon\Carbon::today())->get()->count()) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div><!-- End Customers Card -->
                </div>
            </div>
            <div class="mb-2 col-xxl-1 col-md-1"></div>
            <div class="mb-2 col-xxl-3 col-md-3">
                <div class="card card-body" style="background:#fff !important;box-shadow: 0px 0px 75px 0px rgba(239, 145, 85, 0.14);">
                    <div class="mb-3 col-md-12">
                        <h6>Device Category</h6>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <h6><i class="fas fa-mobile" style="color:#1A2B88"></i> &nbsp;&nbsp; Mobile</h6>
                        <p class="m-0">96.42%</p>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <h6><i class="fas fa-desktop" style="color:#1A2B88"></i> &nbsp;&nbsp; Desktop</h6>
                        <p class="m-0">2.76%</p>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <h6><i class="fas fa-tablet-alt" style="color:#1A2B88"></i> &nbsp;&nbsp; Tablet</h6>
                        <p class="m-0">0.82%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                @endif
            </div><!-- End Left side columns -->

        </div>
    @endif
</div>
<!-- Sale & Revenue End -->

<script>
    // Helper function to format numbers with commas
    function formatNumberWithCommas(num) {
        if (num === null || num === undefined) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    $(document).ready(function() {
        $(".datepicker").flatpickr();
        infocount();

        $(document).on('change', '#startDate', function(){
            infocount();
        });
        $(document).on('change', '#endDate', function(){
            infocount();
        });

        $(document).on('change', '#fromDate', function(){
            salsecount();
        });
        $(document).on('change', '#toDate', function(){
            salsecount();
        });

        $('#orderFilter').text('/Today');
        $('#topsellProduct').text('/Today');

        //topsell products
        $.ajax({
            type: "get",
            url: "{{ url('admin_order/product/topsell/0') }}",
            contentType: "application/json",
            success: function(response) {
                var data = JSON.parse(response);
                $('#topsellProductTbl').html('');
                if (data["status"] == "success") {
                    for (let i = 0; i < data["orders"].length; i++) {
                        $('#topsellProductTbl').append(
                            `
                                <tr>
                                    <th>` + data["orders"][i].productCode + `</th>
                                    <td scope="row"><a href="#"><img src="{{ asset('public/image/default.png') }}" alt=""></a></td>
                                    <td><a href="#" class="text-primary fw-bold">` + data["orders"][i].productName + `</a></td>
                                    <td>TK. ` + formatNumberWithCommas(data["orders"][i].productPrice) + `</td>
                                    <td class="fw-bold">` + formatNumberWithCommas(data["orders"][i].total_amount) + `</td>
                                </tr>
                            `);
                    }
                } else {
                    if (data["status"] == "failed") {
                        swal(data["message"]);
                    } else {
                        swal("Something wrong ! Please try again.");
                    }
                }
            }
        });

        // recent sale
        $.ajax({
            type: "get",
            url: "{{ url('admin_order/product/recentsell/0') }}",
            contentType: "application/json",
            success: function(response) {
                var data = JSON.parse(response);
                $('#recentselltitle').text('/Today');
                $('#recentsellProductTbl').html('');
                if (data["status"] == "success") {
                    for (let i = 0; i < data["orders"].length; i++) {
                        $('#recentsellProductTbl').append(
                            `
                                <tr>
                                    <th>` + data["orders"][i].invoiceID + `</th>
                                    <td>` + data["orders"][i].customers.customerName + `</td>
                                    <td id="recentsellproname` + data["orders"][i].id + `">
                                    </td>
                                    <td>TK. ` + formatNumberWithCommas(data["orders"][i].subTotal) + `</td>
                                    <td class="fw-bold">` + data["orders"][i].status + `</td>
                                </tr>
                            `);
                    }

                    for (let i = 0; i < data["orders"].length; i++) {
                        for (let j = 0; j < data["orders"][i].orderproducts.length; j++) {
                            $('#recentsellproname' + data["orders"][i].id).append(
                                `
                                <a href="#" class="text-primary fw-bold">` + j + `.` + data["orders"][i].orderproducts[
                                    j].productName + `</a><br>
                                `);
                        }
                    }

                } else {
                    if (data["status"] == "failed") {
                        swal(data["message"]);
                    } else {
                        swal("Something wrong ! Please try again.");
                    }
                }
            }
        });
    });

    function infocount(){
        $.ajax({
            type: "get",
            url: "{{ url('admin/info-count') }}",
            data: {
                startDate: function() { return $('#startDate').val() },
                endDate: function() { return $('#endDate').val() }
            },
            contentType: "application/json",
            success: function(response) {
                var data = JSON.parse(response);

                if (data["status"] == "success") {
                    // Format all count numbers
                    $('#pending').text(formatNumberWithCommas(data["pending"]));
                    $('#canceled').text(formatNumberWithCommas(data["canceled"]));
                    $('#confirmed').text(formatNumberWithCommas(data["confirmed"]));
                    $('#processing').text(formatNumberWithCommas(data["processing"]));
                    $('#ontheway').text(formatNumberWithCommas(data["ontheway"]));
                    $('#delivered').text(formatNumberWithCommas(data["delivered"]));
                    $('#return').text(formatNumberWithCommas(data["return"]));
                    $('#packageing').text(formatNumberWithCommas(data["packageing"]));
                    $('#all').text(formatNumberWithCommas(data["all"]));
                    $('#allorder').text(formatNumberWithCommas(data["allorder"]));

                    // Today's report counts
                    $('#to').text(formatNumberWithCommas(data["all"]));
                    $('#tc').text(formatNumberWithCommas(data["confirmed"]));
                    $('#tod').text(formatNumberWithCommas(data["ontheway"]));
                    $('#td').text(formatNumberWithCommas(data["delivered"]));
                    $('#tr').text(formatNumberWithCommas(data["return"]));

                    // Today's report amounts (formatted with commas)
                    $('#toa').text(formatNumberWithCommas(data["toa"]));
                    $('#tca').text(formatNumberWithCommas(data["tca"]));
                    $('#toda').text(formatNumberWithCommas(data["toda"]));
                    $('#tda').text(formatNumberWithCommas(data["tda"]));
                    $('#tra').text(formatNumberWithCommas(data["tra"]));
                    $('#totd').text(formatNumberWithCommas(data["too"]));
                    $('#totda').text(formatNumberWithCommas(data["tooa"]));
                } else {
                    if (data["status"] == "failed") {
                        swal(data["message"]);
                    } else {
                        swal("Something wrong ! Please try again.");
                    }
                }
            }
        });
    }

    function salsecount(){
        $.ajax({
            type: "get",
            url: "{{ url('admin/salse-count') }}",
            data: {
                startDate: function() { return $('#fromDate').val() },
                endDate: function() { return $('#toDate').val() }
            },
            contentType: "application/json",
            success: function(response) {
                var data = JSON.parse(response);

                if (data["status"] == "success") {
                    // Format all count numbers
                    $('#order').text(formatNumberWithCommas(data["order"]));
                    $('#pendingorder').text(formatNumberWithCommas(data["pendingorder"]));
                    $('#pendingprofit').text(formatNumberWithCommas(data["pendingprofit"]));
                    $('#profit').text(formatNumberWithCommas(data["profit"]));
                    $('#resellerorder').text(formatNumberWithCommas(data["resellerorder"]));
                    $('#resellerpendingorder').text(formatNumberWithCommas(data["resellerpendingorder"]));
                    $('#resellerpendingprofit').text(formatNumberWithCommas(data["resellerpendingprofit"]));
                    $('#resellerprofit').text(formatNumberWithCommas(data["resellerprofit"]));

                    // Format total amounts
                    $('#totalpendingprofit').text(formatNumberWithCommas(data["totalpendingprofit"]));
                    $('#totalpendingorder').text(formatNumberWithCommas(data["totalpendingorder"]));
                    $('#totalorder').text(formatNumberWithCommas(data["totalorder"]));
                    $('#totalprofit').text(formatNumberWithCommas(data["totalprofit"]));
                } else {
                    if (data["status"] == "failed") {
                        swal(data["message"]);
                    } else {
                        swal("Something wrong ! Please try again.");
                    }
                }
            }
        });
    }

    function recentsellfilter(id) {
        // recent sale
        $.ajax({
            type: "get",
            url: "{{ url('admin_order/product/recentsell/') }}" + '/' + id,
            contentType: "application/json",
            success: function(response) {
                var data = JSON.parse(response);
                $('#recentselltitle').text(data.title);
                $('#recentsellProductTbl').html('');
                if (data["status"] == "success") {
                    for (let i = 0; i < data["orders"].length; i++) {
                        $('#recentsellProductTbl').append(
                            `
                                <tr>
                                    <th>` + data["orders"][i].invoiceID + `</th>
                                    <td>` + data["orders"][i].customers.customerName + `</td>
                                    <td id="recentsellproname` + data["orders"][i].id + `">
                                    </td>
                                    <td>TK. ` + formatNumberWithCommas(data["orders"][i].subTotal) + `</td>
                                    <td class="fw-bold">` + data["orders"][i].status + `</td>
                                </tr>
                            `);
                    }

                    for (let i = 0; i < data["orders"].length; i++) {
                        for (let j = 0; j < data["orders"][i].orderproducts.length; j++) {
                            $('#recentsellproname' + data["orders"][i].id).append(
                                `
                                <a href="#" class="text-primary fw-bold">` + j + `.` + data["orders"][i].orderproducts[
                                    j].productName + `</a><br>
                                `);
                        }
                    }

                } else {
                    if (data["status"] == "failed") {
                        swal(data["message"]);
                    } else {
                        swal("Something wrong ! Please try again.");
                    }
                }
            }
        });
    }

    function topsellfilter(id) {
        $.ajax({
            type: "get",
            url: "{{ url('admin_order/product/topsell/') }}" + '/' + id,
            contentType: "application/json",
            success: function(response) {
                var data = JSON.parse(response);
                $('#topsellProductTbl').html('');
                if (data["status"] == "success") {
                    for (let i = 0; i < data["orders"].length; i++) {
                        $('#topsellProductTbl').append(
                            `
                                <tr>
                                    <th>` + data["orders"][i].productCode + `</th>
                                    <td scope="row"><a href="#"><img src="{{ asset('public/image/default.png') }}" alt=""></a></td>
                                    <td><a href="#" class="text-primary fw-bold">` + data["orders"][i].productName + `</a></td>
                                    <td>TK. ` + formatNumberWithCommas(data["orders"][i].productPrice) + `</td>
                                    <td class="fw-bold">` + formatNumberWithCommas(data["orders"][i].total_amount) + `</td>
                                </tr>
                            `);
                    }
                } else {
                    if (data["status"] == "failed") {
                        swal(data["message"]);
                    } else {
                        swal("Something wrong ! Please try again.");
                    }
                }
            }
        });
    }

    function orderfilter(id) {
        $.ajax({
            type: "GET",
            url: "{{ url('admin_order/count/') }}" + '/' + id,
            contentType: "application/json",
            success: function(response) {
                var data = JSON.parse(response);
                if (data["status"] == "success") {
                    $('#orderFilter').text(data["title"]);
                    $('orderFilter').text('Today');
                    
                    // Format all count numbers
                    $('#delivered').text(formatNumberWithCommas(data["delivered"]));
                    $('#customerConfirm').text(formatNumberWithCommas(data["customerConfirm"]));
                    $('#paid').text(formatNumberWithCommas(data["paid"]));
                    $('#return').text(formatNumberWithCommas(data["return"]));
                    $('#lost').text(formatNumberWithCommas(data["lost"]));
                    $('#pendingInvoiced').text(formatNumberWithCommas(data["pendingInvoiced"]));
                    $('#invoiced').text(formatNumberWithCommas(data["invoiced"]));
                    $('#stockOut').text(formatNumberWithCommas(data["stockOut"]));
                    $('#all').text(formatNumberWithCommas(data["all"]));
                    $('#allorder').text(formatNumberWithCommas(data["allorder"]));
                    $('#processing').text(formatNumberWithCommas(data["processing"]));
                    $('#pendingPayment').text(formatNumberWithCommas(data["pendingPayment"]));
                    $('#onHold').text(formatNumberWithCommas(data["onHold"]));
                    $('#canceled').text(formatNumberWithCommas(data["canceled"]));
                    $('#completed').text(formatNumberWithCommas(data["completed"]));
                } else {
                    if (data["status"] == "failed") {
                        swal(data["message"]);
                    } else {
                        swal("Something wrong ! Please try again.");
                    }
                }
            }
        });
    }

    window.onload = function () {
        var chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: true,
            legend:{
                cursor: "pointer",
                itemclick: explodePie
            },
            data: [{
                type: "pie",
                showInLegend: true,
                toolTipContent: "{name}: <strong>{y}%</strong>",
                indexLabel: "{name} - {y}%",
                dataPoints: [
                    { y: 26, name: "Pending", exploded: true },
                    { y: 20, name: "Canceled" },
                    { y: 5, name: "Confirmed" },
                    { y: 3, name: "Invoiced" },
                    { y: 7, name: "On Delivery" },
                    { y: 17, name: "Delivered" },
                    { y: 22, name: "Return"}
                ]
            }]
        });
        chart.render();
    }

    function explodePie (e) {
        if(typeof (e.dataSeries.dataPoints[e.dataPointIndex].exploded) === "undefined" || !e.dataSeries.dataPoints[e.dataPointIndex].exploded) {
            e.dataSeries.dataPoints[e.dataPointIndex].exploded = true;
        } else {
            e.dataSeries.dataPoints[e.dataPointIndex].exploded = false;
        }
        e.chart.render();
    }
</script>
@endsection
