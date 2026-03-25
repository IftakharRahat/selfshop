@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Product Reviews
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Product Reviews</li>
            </ol>
        </nav>
    </div>

    @if(session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Product Reviews</h6>
            <p class="mb-0" style="font-size: 12px; color: #64748b;">All customer reviews. Change status to Active/Inactive.</p>
        </div>
        <div class="admin-card-body">
            <form method="get" class="row g-2 mb-0">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Product name" value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <select name="rating" class="form-select form-select-sm">
                        <option value="">All ratings</option>
                        @for($i=5;$i>=1;$i--)
                        <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} Star</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Filter</button>
                </div>
            </form>
        </div>
        <div class="admin-card-body p-0" style="border-top: 1px solid var(--admin-border, #f1f5f9);">
            <div class="table-responsive">
                <table class="table admin-table mb-0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>@if($r->product) {{ Str::limit($r->product->ProductName, 25) }} @else - @endif</td>
                            <td>@if($r->product && $r->product->vendor) {{ $r->product->vendor->company_name }} @else - @endif</td>
                            <td>{{ $r->user->name ?? $r->user->email ?? '-' }}</td>
                            <td><span style="color: #f59e0b;">{{ $r->rating }} ★</span></td>
                            <td>{{ Str::limit($r->messages, 40) }}</td>
                            <td>
                                @if($r->status === 'Active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge" style="background: #e2e8f0; color: #64748b;">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $r->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <form action="{{ url('admin/reviews/'.$r->id.'/status') }}" method="post" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $r->status === 'Active' ? 'Inactive' : 'Active' }}">
                                    <button type="submit" class="btn btn-sm {{ $r->status === 'Active' ? 'btn-warning' : 'btn-success' }}">
                                        {{ $r->status === 'Active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No reviews found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card-body d-flex justify-content-center">{{ $reviews->withQueryString()->links('vendor.pagination.admin') }}</div>
    </div>
</div>
@endsection
