@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - {{ $vendor->company_name }} Sales Overview
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('admin/view-vendor-payout-requests/pending') }}">Payout Requests</a></li>
                <li class="breadcrumb-item active">{{ $vendor->company_name }}</li>
            </ol>
        </nav>
    </div>

    {{-- Supplier Header --}}
    <div class="admin-content-card mb-3">
        <div class="admin-card-body d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
            <div>
                <h5 class="mb-1 fw-bold" style="color: #2d2a5d;">{{ $vendor->company_name }}</h5>
                <p class="small text-muted mb-0">
                    {{ $vendor->contact_phone ?? $vendor->user?->phone ?? '-' }} &middot;
                    {{ $vendor->contact_email ?? $vendor->user?->email ?? '-' }} &middot;
                    Status: <span class="badge {{ $vendor->status === 'approved' ? 'bg-success' : ($vendor->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($vendor->status) }}</span>
                </p>
            </div>
            <a href="{{ url('admin/view-vendor-payout-requests/pending') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to Payout Requests</a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2">
            <div class="admin-content-card h-100">
                <div class="admin-card-body text-center py-3">
                    <p class="small text-muted mb-1">Total Products</p>
                    <h4 class="mb-0 fw-bold" style="color: #2d2a5d;">{{ $productCount }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="admin-content-card h-100">
                <div class="admin-card-body text-center py-3">
                    <p class="small text-muted mb-1">Total Orders</p>
                    <h4 class="mb-0 fw-bold" style="color: #2d2a5d;">{{ $totalOrders }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="admin-content-card h-100">
                <div class="admin-card-body text-center py-3">
                    <p class="small text-muted mb-1">Net Earnings</p>
                    <h4 class="mb-0 fw-bold" style="color: #16a34a;">৳{{ number_format($netEarnings, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="admin-content-card h-100">
                <div class="admin-card-body text-center py-3">
                    <p class="small text-muted mb-1">Available Balance</p>
                    <h4 class="mb-0 fw-bold" style="color: #0284c7;">৳{{ number_format($availableBalance, 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Financial Details + Orders by Status --}}
    <div class="row mb-3">
        <div class="col-md-6 mb-2">
            <div class="admin-content-card h-100">
                <div class="admin-card-header"><h6 class="admin-card-title">Financial Details</h6></div>
                <div class="admin-card-body">
                    <table class="table table-sm table-borderless mb-0 small">
                        <tr><td class="text-muted">Total Sales (Gross)</td><td class="text-end fw-bold">৳{{ number_format($totalSales, 2) }}</td></tr>
                        <tr><td class="text-muted">Commission Deducted</td><td class="text-end fw-bold text-danger">-৳{{ number_format($totalCommission, 2) }}</td></tr>
                        <tr style="border-top: 1px solid #e2e8f0;"><td class="text-muted">Net Earnings</td><td class="text-end fw-bold">৳{{ number_format($netEarnings, 2) }}</td></tr>
                        <tr><td class="text-muted">Pending (undelivered)</td><td class="text-end fw-bold" style="color: #d97706;">৳{{ number_format($pendingBalance, 2) }}</td></tr>
                        <tr><td class="text-muted">Available to Withdraw</td><td class="text-end fw-bold" style="color: #0284c7;">৳{{ number_format($availableBalance, 2) }}</td></tr>
                        <tr><td class="text-muted">Already Paid Out</td><td class="text-end fw-bold" style="color: #16a34a;">৳{{ number_format($paidTotal, 2) }}</td></tr>
                        @if($pendingPayoutAmount > 0)
                            <tr style="border-top: 1px solid #fde68a; background: #fffbeb;"><td class="text-muted">Pending Payout Request</td><td class="text-end fw-bold" style="color: #b45309;">৳{{ number_format($pendingPayoutAmount, 2) }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-2">
            <div class="admin-content-card h-100">
                <div class="admin-card-header"><h6 class="admin-card-title">Orders by Status</h6></div>
                <div class="admin-card-body">
                    @if(empty($ordersByStatus))
                        <p class="small text-muted mb-0">No orders yet.</p>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                $statusColors = [
                                    'Delivered' => 'bg-success', 'Pending' => 'bg-warning text-dark',
                                    'Confirmed' => 'bg-info text-dark', 'Ontheway' => 'bg-primary',
                                    'Canceled' => 'bg-danger', 'Return' => 'bg-secondary',
                                    'Paid' => 'bg-success', 'Lost' => 'bg-dark',
                                ];
                            @endphp
                            @foreach($ordersByStatus as $status => $count)
                                <span class="badge {{ $statusColors[$status] ?? 'bg-secondary' }} px-3 py-2" style="font-size: 13px;">{{ $status }}: {{ $count }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Payout Accounts --}}
    <div class="admin-content-card mb-3">
        <div class="admin-card-header"><h6 class="admin-card-title">Payout Accounts</h6></div>
        <div class="admin-card-body">
            @if($vendor->payoutAccounts->isEmpty())
                <p class="small text-danger mb-0"><i class="bi bi-exclamation-triangle me-1"></i>No payout account added by this supplier.</p>
            @else
                <div class="row">
                    @foreach($vendor->payoutAccounts as $acct)
                        <div class="col-md-4 col-sm-6 mb-2">
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; background: {{ $acct->is_default ? '#f0fdf4' : '#f8fafc' }};">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong class="small">{{ ucfirst($acct->channel_type) }}</strong>
                                    @if($acct->is_default)
                                        <span class="badge bg-success" style="font-size: 10px;">Default</span>
                                    @endif
                                </div>
                                @if($acct->provider_name)
                                    <p class="small text-muted mb-1">{{ $acct->provider_name }}</p>
                                @endif
                                <p class="small mb-1"><strong>Name:</strong> {{ $acct->account_name }}</p>
                                <p class="small mb-0"><strong>Number:</strong> {{ $acct->account_number }}</p>
                                @if($acct->routing_number)
                                    <p class="small mb-0"><strong>Routing:</strong> {{ $acct->routing_number }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Top Selling Products (Delivered) --}}
    <div class="admin-content-card mb-3">
        <div class="admin-card-header"><h6 class="admin-card-title">Top Selling Products (Delivered)</h6></div>
        <div class="admin-card-body p-0">
            @if($topProducts->isEmpty())
                <p class="small text-muted text-center py-4 mb-0">No delivered sales yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table admin-table mb-0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th><th>Product Name</th><th class="text-center">Qty Sold</th>
                                <th class="text-end">Revenue</th><th class="text-end">Net Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $i => $tp)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $tp->ProductName }}</td>
                                    <td class="text-center">{{ (int)$tp->total_qty }}</td>
                                    <td class="text-end">৳{{ number_format((float)$tp->total_revenue, 2) }}</td>
                                    <td class="text-end">৳{{ number_format((float)$tp->total_net, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="admin-content-card mb-3" id="recent-orders">
        <div class="admin-card-header"><h6 class="admin-card-title">Recent Orders</h6></div>
        <div class="admin-card-body p-0">
            @if($recentOrders->isEmpty())
                <p class="small text-muted text-center py-4 mb-0">No orders yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table admin-table mb-0" width="100%">
                        <thead>
                            <tr>
                                <th>Invoice</th><th>Date</th><th>Customer</th>
                                <th class="text-end">Total</th><th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $ord)
                                <tr>
                                    <td><a href="{{ url('/admin/vieworder/'.$ord->id) }}" style="color: #2d2a5d; text-decoration: underline;">{{ $ord->invoiceID }}</a></td>
                                    <td>{{ $ord->orderDate ?? $ord->created_at?->format('Y-m-d') }}</td>
                                    <td>{{ $ord->customers?->customerName ?? '-' }}</td>
                                    <td class="text-end">৳{{ number_format((float)$ord->subTotal, 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge {{ $statusColors[$ord->status] ?? 'bg-secondary' }}">{{ $ord->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $recentOrders->fragment('recent-orders')->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>

    {{-- All Products Listed --}}
    <div class="admin-content-card mb-3" id="all-products">
        <div class="admin-card-header"><h6 class="admin-card-title">All Products Listed ({{ $productCount }})</h6></div>
        <div class="admin-card-body p-0">
            @if($allProducts->isEmpty())
                <p class="small text-muted text-center py-4 mb-0">No products listed yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table admin-table mb-0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th><th>Product Name</th>
                                <th class="text-end">Price</th><th class="text-center">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allProducts as $i => $p)
                                <tr>
                                    <td>{{ $allProducts->firstItem() + $i }}</td>
                                    <td>{{ $p->ProductName }}</td>
                                    <td class="text-end">৳{{ number_format((float)($p->ProductSalePrice ?: $p->ProductRegularPrice), 2) }}</td>
                                    <td class="text-center">{{ (int)($p->qty ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $allProducts->fragment('all-products')->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
