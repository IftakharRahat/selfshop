@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Supplier Products
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Supplier Products</li>
            </ol>
        </nav>
    </div>

    @if(session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Supplier Products</h6>
            <p class="mb-0" style="font-size: 12px; color: #64748b;">Products added by suppliers. Verify and approve to make them visible on the storefront.</p>
        </div>
        <div class="admin-card-body">
            <form method="get" class="row g-2 mb-0">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Product name or SKU" value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All approval status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                            <th>Image</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Type</th>
                            <th>Approval</th>
                            <th>Added</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>
                                @if($p->ViewProductImage)
                                    <img src="../{{ $p->ViewProductImage }}" alt="" height="40" style="object-fit:cover; border-radius: 6px;">
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ Str::limit($p->ProductName, 35) }}</td>
                            <td>
                                @if($p->vendor)
                                    {{ $p->vendor->company_name }}
                                    @if($p->vendor->user)
                                        <br><small class="text-muted">{{ $p->vendor->user->email }}</small>
                                    @endif
                                @else - @endif
                            </td>
                            <td>{{ $p->categories->category_name ?? '-' }}</td>
                            <td>{{ $p->ProductSku }}</td>
                            <td>
                                @if(($p->selling_type ?? 'both') === 'wholesale')
                                    <span class="badge bg-success">Wholesale</span>
                                @elseif(($p->selling_type ?? 'both') === 'dropshipping')
                                    <span class="badge bg-info">Dropshipping</span>
                                @else
                                    <span class="badge bg-warning text-dark">Both</span>
                                @endif
                            </td>
                            <td>
                                @if($p->vendor_approval_status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($p->vendor_approval_status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td>{{ $p->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ url('admin/products/'.$p->id.'/edit') }}" class="btn btn-sm btn-outline-primary mb-1">Edit</a>
                                @if($p->vendor_approval_status !== 'approved')
                                    <form action="{{ url('admin/vendor-products/'.$p->id.'/approve') }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success mb-1">Approve</button>
                                    </form>
                                @endif
                                @if($p->vendor_approval_status !== 'rejected')
                                    <form action="{{ url('admin/vendor-products/'.$p->id.'/reject') }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger mb-1">Reject</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No supplier products yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card-body d-flex justify-content-center">{{ $products->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
