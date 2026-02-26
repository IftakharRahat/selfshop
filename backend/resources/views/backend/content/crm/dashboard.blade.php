@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - CRM Dashboard
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">CRM Dashboard</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card mb-4">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Datewise Filtering</h6>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.crm.dashboard') }}" class="row g-2 align-items-end mb-0">
                <div class="col-md-3">
                    <label class="form-label mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff;">Apply Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
            @if($from || $to)
                <div class="mt-2 small text-muted">
                    Showing data for:
                    <strong>{{ $from ?: 'Beginning' }}</strong> to <strong>{{ $to ?: 'Today' }}</strong>
                </div>
            @endif
        </div>
    </div>

    <div class="admin-content-card mb-4">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Company Business Balance Analytics</h6>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded" style="border: 1px solid var(--admin-border, #e2e8f0); background: #f8fafc;">
                        <div class="small text-muted mb-1">Total Sales Revenue</div>
                        <div style="font-size: 24px; font-weight: 700;">{{ number_format($totalSalesRevenue, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded" style="border: 1px solid var(--admin-border, #e2e8f0); background: #f8fafc;">
                        <div class="small text-muted mb-1">Total Revenue by Commission</div>
                        <div style="font-size: 24px; font-weight: 700;">{{ number_format($totalCommissionRevenue, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded" style="border: 1px solid var(--admin-border, #e2e8f0); background: #f8fafc;">
                        <div class="small text-muted mb-1">Total Revenue by Subscription</div>
                        <div style="font-size: 24px; font-weight: 700;">{{ number_format($totalSubscriptionRevenue, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-content-card mb-4" id="user-analytics">
        <div class="admin-card-header">
            <h6 class="admin-card-title">User Analytics</h6>
        </div>
        <div class="admin-card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4 col-lg-2">
                    <a href="{{ route('admin.crm.users') }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">All User</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($allUsers) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 col-lg-2">
                    <a href="{{ route('admin.crm.users', ['status' => 'Active']) }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Active User</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($activeUsers) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 col-lg-2">
                    <a href="{{ route('admin.crm.users', ['membership' => 'Paid']) }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Total Paid User</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($paidUsers) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 col-lg-2">
                    <a href="{{ route('admin.crm.users', ['membership' => 'Unpaid']) }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Total Unpaid User</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($unpaidUsers) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-8 col-lg-4">
                    <a href="{{ route('admin.crm.users') }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Total User Account Balance</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($totalUserAccountBalance, 2) }}</div>
                        </div>
                    </a>
                </div>
            </div>

            <h6 class="mb-2" style="font-weight: 600;">Top 10 Users (By Account Balance)</h6>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Membership</th>
                            <th>Account Balance</th>
                            <th>Account Edit Option</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userAccounts as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge {{ $user->status === 'Active' ? 'bg-success' : ($user->status === 'Block' ? 'bg-danger' : 'bg-secondary') }}">
                                        {{ $user->status ?? 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $user->membership_status ?? 'Unpaid' }}</td>
                                <td>{{ number_format((float) $user->account_balance, 2) }}</td>
                                <td>
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary">Edit Account</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-content-card mb-4" id="supplier-analytics">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Supplier Analytics</h6>
        </div>
        <div class="admin-card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4 col-lg-2">
                    <a href="{{ route('admin.crm.suppliers', ['status' => 'approved']) }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Active Supplier</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($activeSuppliers) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 col-lg-2">
                    <a href="{{ route('admin.crm.suppliers', ['status' => 'pending']) }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Pending Supplier</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($pendingSuppliers) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 col-lg-2">
                    <a href="{{ url('admin/view-vendor-payout-requests/approved') }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Total Payment</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($totalSupplierPayment, 2) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 col-lg-2">
                    <a href="{{ url('admin/view-vendor-payout-requests/pending') }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">Pending Payment</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($pendingSupplierPayment, 2) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-8 col-lg-4">
                    <a href="{{ route('admin.crm.suppliers') }}" class="text-decoration-none">
                        <div class="p-3 rounded h-100" style="border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div class="small text-muted">All Supplier Account Balance</div>
                            <div style="font-size: 22px; font-weight: 700;">{{ number_format($totalSupplierAccountBalance, 2) }}</div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6" id="supplier-leaderboard">
                    <h6 class="mb-2" style="font-weight: 600;">Supplier Leaderboard (Top 10)</h6>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Supplier</th>
                                    <th>Orders</th>
                                    <th>Sales</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supplierLeaderboard as $idx => $supplier)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $supplier->company_name }}</td>
                                        <td>{{ number_format((int) $supplier->order_count) }}</td>
                                        <td>{{ number_format((float) $supplier->sales_total, 2) }}</td>
                                        <td>{{ number_format((float) $supplier->commission_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No supplier leaderboard data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-6">
                    <h6 class="mb-2" style="font-weight: 600;">Top 10 Supplier Accounts (By Balance)</h6>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Status</th>
                                    <th>Account Balance</th>
                                    <th>Account Edit Option</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supplierAccounts as $supplier)
                                    <tr>
                                        <td>
                                            {{ $supplier->company_name }}<br>
                                            <small class="text-muted">{{ $supplier->user_email }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $supplier->status === 'approved' ? 'bg-success' : ($supplier->status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                {{ ucfirst($supplier->status) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format((float) $supplier->account_balance, 2) }}</td>
                                        <td>
                                            <a href="{{ route('admin.vendors.edit', $supplier->id) }}" class="btn btn-sm btn-primary">Edit Account</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No supplier accounts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
