@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - CRM Suppliers
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.crm.dashboard') }}">CRM</a></li>
                <li class="breadcrumb-item active">Suppliers</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3 mb-3">
        <div class="col">
            <div class="admin-content-card">
                <div class="admin-card-body text-center px-1 py-3">
                    <div class="small text-muted" style="font-size: 11px;">Total Supplier</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($totalSuppliers) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="admin-content-card">
                <div class="admin-card-body text-center px-1 py-3">
                    <div class="small text-muted" style="font-size: 11px;">Active Supplier</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($activeSuppliers) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="admin-content-card">
                <div class="admin-card-body text-center px-1 py-3">
                    <div class="small text-muted" style="font-size: 11px;">Pending Supplier</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($pendingSuppliers) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="admin-content-card">
                <div class="admin-card-body text-center px-1 py-3">
                    <div class="small text-muted" style="font-size: 11px;">Total Products</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($totalProducts) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="admin-content-card">
                <div class="admin-card-body text-center px-1 py-3">
                    <div class="small text-muted" style="font-size: 11px;">Total Payment</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($totalSupplierPayment, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="admin-content-card">
                <div class="admin-card-body text-center px-1 py-3">
                    <div class="small text-muted" style="font-size: 11px;">Pending Payment</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($pendingSupplierPayment, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="admin-content-card">
                <div class="admin-card-body text-center px-1 py-3">
                    <div class="small text-muted" style="font-size: 11px;">All App Balance</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($totalSupplierAccountBalance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">CRM Suppliers</h6>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.crm.suppliers') }}" class="row g-2 mb-0">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search supplier, contact, email" value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All status</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff;">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.crm.suppliers') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
        <div class="admin-card-body p-0" style="border-top: 1px solid var(--admin-border, #f1f5f9);">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Products</th>
                            <th>Orders</th>
                            <th>Sales</th>
                            <th>Commission</th>
                            <th>Account Balance</th>
                            <th>Created</th>
                            <th>Account Edit Option</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->id }}</td>
                                <td>
                                    {{ $supplier->company_name }}<br>
                                    <small class="text-muted">{{ $supplier->user_email }}</small>
                                </td>
                                <td>
                                    {{ $supplier->contact_name ?: '-' }}<br>
                                    <small class="text-muted">{{ $supplier->contact_email ?: ($supplier->contact_phone ?: '-') }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $supplier->status === 'approved' ? 'bg-success' : ($supplier->status === 'pending' ? 'bg-warning text-dark' : ($supplier->status === 'rejected' ? 'bg-danger' : 'bg-secondary')) }}">
                                        {{ ucfirst($supplier->status) }}
                                    </span>
                                </td>
                                <td>{{ number_format((int) $supplier->products_count) }}</td>
                                <td>{{ number_format((int) $supplier->order_count) }}</td>
                                <td>{{ number_format((float) $supplier->sales_total, 2) }}</td>
                                <td>{{ number_format((float) $supplier->commission_total, 2) }}</td>
                                <td>{{ number_format((float) $supplier->account_balance, 2) }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($supplier->created_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.vendors.edit', $supplier->id) }}" class="btn btn-sm btn-primary">Edit Account</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No suppliers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card-body d-flex justify-content-center">
            {{ $suppliers->links('vendor.pagination.admin') }}
        </div>
    </div>
</div>
@endsection

