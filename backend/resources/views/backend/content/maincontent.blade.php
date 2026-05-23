@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }}-Admin
@endsection

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

<div class="px-4 pt-4 container-fluid">

    {{-- Page Header --}}
    <div class="dash-page-header">
        <h4>Dashboard</h4>
        <div class="dash-header-meta">
            <i class="bi bi-calendar3"></i> {{ date('l, F j, Y') }}
        </div>
    </div>

    @if ($admin->hasRole('user'))
        {{-- ═══════════════════════════════════════════
            USER ROLE VIEW
        ═══════════════════════════════════════════ --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('/user/order') }}" class="dash-card">
                    <span class="dash-card-label">All Orders</span>
                    <div class="dash-card-value"><span id="all">0</span></div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('admin_order/Processing') }}" class="dash-card">
                    <span class="dash-card-label">Processing</span>
                    <div class="dash-card-value"><span id="processing">0</span></div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('admin_order/Canceled') }}" class="dash-card">
                    <span class="dash-card-label">Canceled</span>
                    <div class="dash-card-value"><span id="canceled">0</span></div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('admin_order/Completed') }}" class="dash-card">
                    <span class="dash-card-label">Completed</span>
                    <div class="dash-card-value"><span id="completed">0</span></div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('admin_order/Packageing') }}" class="dash-card">
                    <span class="dash-card-label">Packaging</span>
                    <div class="dash-card-value"><span id="packageing">0</span></div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('admin_order/Ontheway') }}" class="dash-card">
                    <span class="dash-card-label">On the Way</span>
                    <div class="dash-card-value"><span id="ontheway">0</span></div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('admin_order/Delivered') }}" class="dash-card">
                    <span class="dash-card-label">Delivered</span>
                    <div class="dash-card-value"><span id="delivered">0</span></div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ url('admin_order/Return') }}" class="dash-card">
                    <span class="dash-card-label">Return</span>
                    <div class="dash-card-value"><span id="return">0</span></div>
                </a>
            </div>
        </div>
    @else
        {{-- ═══════════════════════════════════════════
            ADMIN / SHOP ROLE VIEW
        ═══════════════════════════════════════════ --}}

        {{-- 1. ORDER OVERVIEW — stat cards + date filter + pie chart --}}
        <div class="dash-section">
            <div class="dash-toolbar">
                <div class="dash-presets" id="orderPresets">
                    <button class="dash-preset-btn active" data-range="today" onclick="datePreset('order','today',this)">Today</button>
                    <button class="dash-preset-btn" data-range="week" onclick="datePreset('order','week',this)">This Week</button>
                    <button class="dash-preset-btn" data-range="month" onclick="datePreset('order','month',this)">This Month</button>
                    <button class="dash-preset-btn" data-range="year" onclick="datePreset('order','year',this)">This Year</button>
                    <button class="dash-preset-btn" data-range="all" onclick="datePreset('order','all',this)">All Time</button>
                </div>
                <div class="dash-filter-group">
                    <label>From</label>
                    <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="startDate">
                </div>
                <div class="dash-filter-group">
                    <label>To</label>
                    <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="endDate">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/orderall') }}" class="dash-card">
                        <span class="dash-card-label">Total Sales</span>
                        <div class="dash-card-value">৳ <span id="totalsalesamount">0</span></div>
                        <div class="dash-card-sub"><span id="totalsalesorders">0</span> orders excluding canceled/return</div>
                        <div class="dash-card-icon"><i class="bi bi-cash-stack"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/orderall') }}" class="dash-card">
                        <span class="dash-card-label">All Orders</span>
                        <div class="dash-card-value"><span id="all">0</span></div>
                        <div class="dash-card-sub">৳ <span id="allamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-stack"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Pending') }}" class="dash-card">
                        <span class="dash-card-label">Pending</span>
                        <div class="dash-card-value"><span id="pending">0</span></div>
                        <div class="dash-card-sub">৳ <span id="pendingamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-clock"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Confirmed') }}" class="dash-card">
                        <span class="dash-card-label">Confirmed</span>
                        <div class="dash-card-value"><span id="confirmed">0</span></div>
                        <div class="dash-card-sub">৳ <span id="confirmedamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-check-circle"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Canceled') }}" class="dash-card">
                        <span class="dash-card-label">Canceled</span>
                        <div class="dash-card-value"><span id="canceled">0</span></div>
                        <div class="dash-card-sub">৳ <span id="canceledamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-x-circle"></i></div>
                    </a>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Processing') }}" class="dash-card">
                        <span class="dash-card-label">Processing</span>
                        <div class="dash-card-value"><span id="processing">0</span></div>
                        <div class="dash-card-sub">৳ <span id="processingamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-gear"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Packageing') }}" class="dash-card">
                        <span class="dash-card-label">Packaging</span>
                        <div class="dash-card-value"><span id="packageing">0</span></div>
                        <div class="dash-card-sub">৳ <span id="packageingamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-box-seam"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Ontheway') }}" class="dash-card">
                        <span class="dash-card-label">On the Way</span>
                        <div class="dash-card-value"><span id="ontheway">0</span></div>
                        <div class="dash-card-sub">৳ <span id="onthewayamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-truck"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Delivered') }}" class="dash-card">
                        <span class="dash-card-label">Delivered</span>
                        <div class="dash-card-value"><span id="delivered">0</span></div>
                        <div class="dash-card-sub">৳ <span id="deliveredamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-check2-all"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ url('admin_order/Return') }}" class="dash-card">
                        <span class="dash-card-label">Return</span>
                        <div class="dash-card-value"><span id="return">0</span></div>
                        <div class="dash-card-sub">৳ <span id="returnamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-arrow-return-left"></i></div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <a href="#" class="dash-card">
                        <span class="dash-card-label">Others</span>
                        <div class="dash-card-value"><span id="others">0</span></div>
                        <div class="dash-card-sub">৳ <span id="othersamount">0</span></div>
                        <div class="dash-card-icon"><i class="bi bi-three-dots"></i></div>
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. PROFIT & LOSS --}}
        <div class="dash-section">
            <h6 class="dash-section-title">Profit & Loss Report</h6>

            <div class="dash-toolbar">
                <div class="dash-presets" id="profitPresets">
                    <button class="dash-preset-btn active" data-range="today" onclick="datePreset('profit','today',this)">Today</button>
                    <button class="dash-preset-btn" data-range="week" onclick="datePreset('profit','week',this)">This Week</button>
                    <button class="dash-preset-btn" data-range="month" onclick="datePreset('profit','month',this)">This Month</button>
                    <button class="dash-preset-btn" data-range="year" onclick="datePreset('profit','year',this)">This Year</button>
                    <button class="dash-preset-btn" data-range="all" onclick="datePreset('profit','all',this)">All Time</button>
                </div>
                <div class="dash-filter-group">
                    <label>From</label>
                    <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="fromDate">
                </div>
                <div class="dash-filter-group">
                    <label>To</label>
                    <input type="date" class="form-control datepicker" value="{{date('Y-m-d')}}" id="toDate">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl">
                    <div class="dash-card">
                        <span class="dash-card-label">Profit</span>
                        <div class="dash-card-value">৳ <span id="profit">0</span></div>
                        <div class="dash-card-sub"><span id="order">0</span> orders</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="dash-card">
                        <span class="dash-card-label">Reseller Profit</span>
                        <div class="dash-card-value">৳ <span id="resellerprofit">0</span></div>
                        <div class="dash-card-sub"><span id="resellerorder">0</span> orders</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="dash-card">
                        <span class="dash-card-label">Total Profit</span>
                        <div class="dash-card-value">৳ <span id="totalprofit">{{ \App\Models\Order::where('orderDate',date('Y-m-d'))->sum('subTotal') }}</span></div>
                        <div class="dash-card-sub"><span id="totalorder">0</span> orders</div>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl">
                    <div class="dash-card">
                        <span class="dash-card-label">Pending Profit</span>
                        <div class="dash-card-value">৳ <span id="pendingprofit">0</span></div>
                        <div class="dash-card-sub"><span id="pendingorder">0</span> orders</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="dash-card">
                        <span class="dash-card-label">Pending Reseller</span>
                        <div class="dash-card-value">৳ <span id="resellerpendingprofit">0</span></div>
                        <div class="dash-card-sub"><span id="resellerpendingorder">0</span> orders</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="dash-card">
                        <span class="dash-card-label">Pending Total</span>
                        <div class="dash-card-value">৳ <span id="totalpendingprofit">0</span></div>
                        <div class="dash-card-sub"><span id="totalpendingorder">0</span> orders</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. SALES REPORT --}}
        <div class="dash-section">
            <h6 class="dash-section-title">Sales Report</h6>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="dash-card">
                        <span class="dash-card-label">Total Sales</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::where('status','Delivered')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::where('status','Delivered')->sum('subTotal'), 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="dash-card">
                        <span class="dash-card-label">This Year</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::whereYear('orderDate',Carbon\Carbon::now()->year)->where('status','Delivered')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::whereYear('orderDate',Carbon\Carbon::now()->year)->where('status','Delivered')->sum('subTotal'), 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <?php
                        $monthSubTotal = \App\Models\Order::where('status','Delivered')
                            ->whereMonth('orderDate', Carbon\Carbon::now()->month)
                            ->whereYear('orderDate', Carbon\Carbon::now()->year)
                            ->sum('subTotal');
                        $monthPaymentAmount = \App\Models\Order::where('status','Delivered')
                            ->whereMonth('orderDate', Carbon\Carbon::now()->month)
                            ->whereYear('orderDate', Carbon\Carbon::now()->year)
                            ->sum('paymentAmount');
                        $monthTotal = $monthSubTotal + $monthPaymentAmount;
                    ?>
                    <div class="dash-card">
                        <span class="dash-card-label">This Month</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::whereMonth('orderDate', Carbon\Carbon::now()->month)->whereYear('orderDate', Carbon\Carbon::now()->year)->where('status','Delivered')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format($monthTotal, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="dash-card">
                        <span class="dash-card-label">Today's Sales</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::where('orderDate', date('Y-m-d'))->where('status','Delivered')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::where('status','Delivered')->where('orderDate', date('Y-m-d'))->sum('subTotal'), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. TODAY'S REPORT --}}
        <div class="dash-section">
            <h6 class="dash-section-title">Today's Report</h6>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Orders</span>
                        <div class="dash-card-value"><span id="to">{{ \App\Models\Order::where('orderDate',date('Y-m-d'))->count() }}</span></div>
                        <div class="dash-card-sub">৳ <span id="toa">{{ \App\Models\Order::where('orderDate',date('Y-m-d'))->sum('subTotal') }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Confirmed</span>
                        <div class="dash-card-value"><span id="tc">{{ \App\Models\Order::where('status','Confirmed')->where('orderDate',date('Y-m-d'))->count() }}</span></div>
                        <div class="dash-card-sub">৳ <span id="tca">{{ \App\Models\Order::where('status','Confirmed')->where('orderDate',date('Y-m-d'))->sum('subTotal') }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">On the Way</span>
                        <div class="dash-card-value"><span id="tod">{{ \App\Models\Order::where('status','Ontheway')->where('orderDate',date('Y-m-d'))->count() }}</span></div>
                        <div class="dash-card-sub">৳ <span id="toda">{{ \App\Models\Order::where('status','Ontheway')->where('orderDate',date('Y-m-d'))->sum('subTotal') }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Delivered</span>
                        <div class="dash-card-value"><span id="td">{{ \App\Models\Order::where('status','Delivered')->where('orderDate',date('Y-m-d'))->count() }}</span></div>
                        <div class="dash-card-sub">৳ <span id="tda">{{ \App\Models\Order::where('status','Delivered')->where('orderDate',date('Y-m-d'))->sum('subTotal') }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Returned</span>
                        <div class="dash-card-value"><span id="tr">{{ \App\Models\Order::where('status','Return')->where('orderDate',date('Y-m-d'))->count() }}</span></div>
                        <div class="dash-card-sub">৳ <span id="tra">{{ \App\Models\Order::where('status','Return')->where('orderDate',date('Y-m-d'))->sum('subTotal') }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Others</span>
                        <div class="dash-card-value"><span id="totd">{{ \App\Models\Order::whereNotIn('status',['Confirmed','Ontheway','Delivered','Return'])->where('orderDate',date('Y-m-d'))->count() }}</span></div>
                        <div class="dash-card-sub">৳ <span id="totda">{{ \App\Models\Order::whereNotIn('status',['Confirmed','Ontheway','Delivered','Return'])->where('orderDate',date('Y-m-d'))->sum('subTotal') }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. TOTAL REPORT --}}
        <div class="dash-section">
            <h6 class="dash-section-title">Total Report (All Time)</h6>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Total Orders</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::sum('subTotal')) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Confirmed</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::where('status','Confirmed')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::where('status','Confirmed')->sum('subTotal')) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">On the Way</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::where('status','Ontheway')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::where('status','Ontheway')->sum('subTotal')) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Delivered</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::where('status','Delivered')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::where('status','Delivered')->sum('subTotal')) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Returned</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::where('status','Return')->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::where('status','Return')->sum('subTotal')) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Other Statuses</span>
                        <div class="dash-card-value">{{ number_format(\App\Models\Order::whereNotIn('status',['Confirmed','Ontheway','Delivered','Return'])->count()) }}</div>
                        <div class="dash-card-sub">৳ {{ number_format(\App\Models\Order::whereNotIn('status',['Confirmed','Ontheway','Delivered','Return'])->sum('subTotal'), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. RESELLER REPORT + PIE CHART + DEVICE CATEGORY --}}
        <div class="dash-section">
            <h6 class="dash-section-title">Reseller Report</h6>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Total Resellers</span>
                        <div class="dash-card-value">{{ number_format(App\Models\User::count()) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Paid Resellers</span>
                        <div class="dash-card-value">{{ number_format(App\Models\User::where('status','Active')->where('membership_status','Paid')->count()) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Unpaid Resellers</span>
                        <div class="dash-card-value">{{ number_format(App\Models\User::where('membership_status','Unpaid')->count()) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Banned</span>
                        <div class="dash-card-value">{{ number_format(App\Models\User::where('status','Block')->count()) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Today's Registered</span>
                        <div class="dash-card-value">{{ number_format(App\Models\User::where('created_at', '>=', Carbon\Carbon::today())->count()) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="dash-card">
                        <span class="dash-card-label">Today's Active</span>
                        <div class="dash-card-value">{{ number_format(App\Models\User::where('active_date', '>=', Carbon\Carbon::today())->count()) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. PIE CHART + DEVICE CATEGORY --}}
        <div class="dash-section">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="dash-chart-card">
                        <div class="dash-chart-title">Order Status Distribution</div>
                        <div class="dash-chart-container" id="chartContainer"></div>
                        <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dash-chart-card">
                        <div class="dash-chart-title">Device Category</div>
                        <div class="dash-device-item">
                            <div class="dash-device-label">
                                <i class="fas fa-mobile"></i> Mobile
                            </div>
                            <div class="dash-device-pct">96.42%</div>
                        </div>
                        <div class="dash-device-item">
                            <div class="dash-device-label">
                                <i class="fas fa-desktop"></i> Desktop
                            </div>
                            <div class="dash-device-pct">2.76%</div>
                        </div>
                        <div class="dash-device-item">
                            <div class="dash-device-label">
                                <i class="fas fa-tablet-alt"></i> Tablet
                            </div>
                            <div class="dash-device-pct">0.82%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @endif
</div>

<script>
    function formatNumberWithCommas(num) {
        if (num === null || num === undefined) return '0';
        var n = parseFloat(num);
        var isInteger = (n % 1 === 0);
        var str = isInteger ? n.toString() : n.toFixed(2);
        var parts = str.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        return parts.join('.');
    }

    function datePreset(section, range, btn) {
        var today = new Date();
        var start, end;
        // Use local date (not UTC) to match server timezone (Asia/Dhaka)
        end = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

        switch (range) {
            case 'today':
                start = end;
                break;
            case 'week':
                var d = new Date(today);
                d.setDate(d.getDate() - d.getDay());
                start = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                break;
            case 'month':
                start = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-01';
                break;
            case 'year':
                start = today.getFullYear() + '-01-01';
                break;
            case 'all':
                start = '2020-01-01';
                break;
            default:
                start = end;
        }

        if (section === 'order') {
            $('#startDate').val(start);
            $('#endDate').val(end);
            if ($('#startDate')[0]._flatpickr) $('#startDate')[0]._flatpickr.setDate(start, false);
            if ($('#endDate')[0]._flatpickr) $('#endDate')[0]._flatpickr.setDate(end, false);
            $('#orderPresets .dash-preset-btn').removeClass('active');
            infocount();
        } else {
            $('#fromDate').val(start);
            $('#toDate').val(end);
            if ($('#fromDate')[0]._flatpickr) $('#fromDate')[0]._flatpickr.setDate(start, false);
            if ($('#toDate')[0]._flatpickr) $('#toDate')[0]._flatpickr.setDate(end, false);
            $('#profitPresets .dash-preset-btn').removeClass('active');
            salsecount();
        }

        if (btn) $(btn).addClass('active');
    }

    $(document).ready(function() {
        $(".datepicker").flatpickr();
        // default to "This Month" on page load
        datePreset('order', 'month', document.querySelector('#orderPresets .dash-preset-btn[data-range="month"]'));
        datePreset('profit', 'month', document.querySelector('#profitPresets .dash-preset-btn[data-range="month"]'));

        $(document).on('change', '#startDate', function(){ infocount(); });
        $(document).on('change', '#endDate', function(){ infocount(); });
        $(document).on('change', '#fromDate', function(){ salsecount(); });
        $(document).on('change', '#toDate', function(){ salsecount(); });

        $('#orderFilter').text('/Today');
        $('#topsellProduct').text('/Today');

        // top selling products
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
                            `<tr>
                                <th>` + data["orders"][i].productCode + `</th>
                                <td scope="row"><a href="#"><img src="{{ asset('public/image/default.png') }}" alt=""></a></td>
                                <td><a href="#" class="text-primary fw-bold">` + data["orders"][i].productName + `</a></td>
                                <td>TK. ` + formatNumberWithCommas(data["orders"][i].productPrice) + `</td>
                                <td class="fw-bold">` + formatNumberWithCommas(data["orders"][i].total_amount) + `</td>
                            </tr>`);
                    }
                } else {
                    if (data["status"] == "failed") { swal(data["message"]); }
                    else { swal("Something wrong ! Please try again."); }
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
                            `<tr>
                                <th>` + data["orders"][i].invoiceID + `</th>
                                <td>` + data["orders"][i].customers.customerName + `</td>
                                <td id="recentsellproname` + data["orders"][i].id + `"></td>
                                <td>TK. ` + formatNumberWithCommas(data["orders"][i].subTotal) + `</td>
                                <td class="fw-bold">` + data["orders"][i].status + `</td>
                            </tr>`);
                    }
                    for (let i = 0; i < data["orders"].length; i++) {
                        for (let j = 0; j < data["orders"][i].orderproducts.length; j++) {
                            $('#recentsellproname' + data["orders"][i].id).append(
                                `<a href="#" class="text-primary fw-bold">` + j + `.` + data["orders"][i].orderproducts[j].productName + `</a><br>`);
                        }
                    }
                } else {
                    if (data["status"] == "failed") { swal(data["message"]); }
                    else { swal("Something wrong ! Please try again."); }
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
                    $('#totalsalesamount').text(formatNumberWithCommas(data["totalsalesamount"]));
                    $('#totalsalesorders').text(formatNumberWithCommas(data["totalsalesorders"]));
                    $('#allamount').text(formatNumberWithCommas(data["allamount"]));
                    $('#pendingamount').text(formatNumberWithCommas(data["pendingamount"]));
                    $('#confirmedamount').text(formatNumberWithCommas(data["confirmedamount"]));
                    $('#canceledamount').text(formatNumberWithCommas(data["canceledamount"]));
                    $('#processingamount').text(formatNumberWithCommas(data["processingamount"]));
                    $('#packageingamount').text(formatNumberWithCommas(data["packageingamount"]));
                    $('#onthewayamount').text(formatNumberWithCommas(data["onthewayamount"]));
                    $('#deliveredamount').text(formatNumberWithCommas(data["deliveredamount"]));
                    $('#returnamount').text(formatNumberWithCommas(data["returnamount"]));
                    $('#othersamount').text(formatNumberWithCommas(data["othersamount"]));

                    // Calculate "Others" as difference between All and tracked statuses
                    $('#others').text(formatNumberWithCommas(data["others"] || 0));

                    // Today's Report values are server-rendered and should NOT
                    // be overwritten by the Order Overview date filter.
                } else {
                    if (data["status"] == "failed") { swal(data["message"]); }
                    else { swal("Something wrong ! Please try again."); }
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
                    $('#order').text(formatNumberWithCommas(data["order"]));
                    $('#pendingorder').text(formatNumberWithCommas(data["pendingorder"]));
                    $('#pendingprofit').text(formatNumberWithCommas(data["pendingprofit"]));
                    $('#profit').text(formatNumberWithCommas(data["profit"]));
                    $('#resellerorder').text(formatNumberWithCommas(data["resellerorder"]));
                    $('#resellerpendingorder').text(formatNumberWithCommas(data["resellerpendingorder"]));
                    $('#resellerpendingprofit').text(formatNumberWithCommas(data["resellerpendingprofit"]));
                    $('#resellerprofit').text(formatNumberWithCommas(data["resellerprofit"]));
                    $('#totalpendingprofit').text(formatNumberWithCommas(data["totalpendingprofit"]));
                    $('#totalpendingorder').text(formatNumberWithCommas(data["totalpendingorder"]));
                    $('#totalorder').text(formatNumberWithCommas(data["totalorder"]));
                    $('#totalprofit').text(formatNumberWithCommas(data["totalprofit"]));
                } else {
                    if (data["status"] == "failed") { swal(data["message"]); }
                    else { swal("Something wrong ! Please try again."); }
                }
            }
        });
    }

    function recentsellfilter(id) {
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
                            `<tr>
                                <th>` + data["orders"][i].invoiceID + `</th>
                                <td>` + data["orders"][i].customers.customerName + `</td>
                                <td id="recentsellproname` + data["orders"][i].id + `"></td>
                                <td>TK. ` + formatNumberWithCommas(data["orders"][i].subTotal) + `</td>
                                <td class="fw-bold">` + data["orders"][i].status + `</td>
                            </tr>`);
                    }
                    for (let i = 0; i < data["orders"].length; i++) {
                        for (let j = 0; j < data["orders"][i].orderproducts.length; j++) {
                            $('#recentsellproname' + data["orders"][i].id).append(
                                `<a href="#" class="text-primary fw-bold">` + j + `.` + data["orders"][i].orderproducts[j].productName + `</a><br>`);
                        }
                    }
                } else {
                    if (data["status"] == "failed") { swal(data["message"]); }
                    else { swal("Something wrong ! Please try again."); }
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
                            `<tr>
                                <th>` + data["orders"][i].productCode + `</th>
                                <td scope="row"><a href="#"><img src="{{ asset('public/image/default.png') }}" alt=""></a></td>
                                <td><a href="#" class="text-primary fw-bold">` + data["orders"][i].productName + `</a></td>
                                <td>TK. ` + formatNumberWithCommas(data["orders"][i].productPrice) + `</td>
                                <td class="fw-bold">` + formatNumberWithCommas(data["orders"][i].total_amount) + `</td>
                            </tr>`);
                    }
                } else {
                    if (data["status"] == "failed") { swal(data["message"]); }
                    else { swal("Something wrong ! Please try again."); }
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
                    if (data["status"] == "failed") { swal(data["message"]); }
                    else { swal("Something wrong ! Please try again."); }
                }
            }
        });
    }

    window.onload = function () {
        var chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: true,
            legend: {
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
                    { y: 22, name: "Return" }
                ]
            }]
        });
        chart.render();
    }

    function explodePie(e) {
        if (typeof(e.dataSeries.dataPoints[e.dataPointIndex].exploded) === "undefined" || !e.dataSeries.dataPoints[e.dataPointIndex].exploded) {
            e.dataSeries.dataPoints[e.dataPointIndex].exploded = true;
        } else {
            e.dataSeries.dataPoints[e.dataPointIndex].exploded = false;
        }
        e.chart.render();
    }
</script>
@endsection
