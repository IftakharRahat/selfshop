@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }}-Refund Claims
@endsection

@php
    $statusColors = [
        'pending' => 'warning',
        'in_progress' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'closed' => 'secondary',
    ];
@endphp

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Refund Claims</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Refund Claims</h6>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.refunds.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Claim, invoice, reseller, product">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('admin.refunds.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Request List</h6>
            <span class="badge bg-primary">{{ $claims->total() }} total</span>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Claim</th>
                            <th>Product</th>
                            <th>Reseller</th>
                            <th>Account</th>
                            <th>Window</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($claims as $claim)
                        @php
                            $account = $payoutAccounts[$claim->id] ?? null;
                            $productImage = $claim->product?->ViewProductImage;
                            $productImageUrl = $productImage
                                ? (\Illuminate\Support\Str::startsWith($productImage, ['http://', 'https://']) ? $productImage : asset($productImage))
                                : null;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $claim->claim_number }}</strong>
                                <div class="text-muted small">{{ optional($claim->created_at)->format('d M Y h:i A') }}</div>
                                <div class="text-muted small">Invoice: {{ $claim->order?->invoiceID ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($productImageUrl)
                                        <img src="{{ $productImageUrl }}" alt="Product" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">
                                    @endif
                                    <div>
                                        <div style="font-weight:600">{{ $claim->orderproduct?->productName ?? $claim->product?->ProductName ?? 'Product' }}</div>
                                        <div class="text-muted small">Qty: {{ $claim->orderproduct?->quantity ?? 1 }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600">{{ $claim->user?->name ?? 'Reseller' }}</div>
                                <div class="text-muted small">{{ $claim->user?->phone ?? $claim->user?->email ?? 'No contact' }}</div>
                            </td>
                            <td>
                                @if($account)
                                    <div style="font-weight:600">{{ $account['label'] ?: 'Account' }}</div>
                                    <div class="text-muted small">{{ $account['number'] }}</div>
                                    <div class="text-muted small">{{ $account['source'] }}</div>
                                @else
                                    <span class="text-muted">No account</span>
                                @endif
                            </td>
                            <td>
                                <div>Delivered: {{ optional($claim->delivery_date)->format('d M Y') }}</div>
                                <div class="text-muted small">Expires: {{ optional($claim->expires_at)->format('d M Y') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusColors[$claim->status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $claim->status)) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.refunds.show', $claim) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-chat-dots me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No refund claims found</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($claims->hasPages())
            <div class="admin-card-body border-top">
                {{ $claims->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
