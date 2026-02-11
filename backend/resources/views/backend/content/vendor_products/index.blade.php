@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Products
@endsection

@section('maincontent')
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <h6 class="mb-0">Vendor Products</h6>
                <p class="text-muted small mb-0">Products added by vendors. Verify and approve to make them visible on the storefront. You can edit any product from here.</p>
            </div>
        </div>
        @if(session('message'))
        <div class="col-12">
            <div class="alert alert-success">{{ session('message') }}</div>
        </div>
        @endif
        <div class="col-sm-12 col-md-12 col-xl-12 mt-2">
            <div class="p-4 rounded bg-secondary h-100">
                <form method="get" class="mb-3 row g-2">
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
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </div>
                </form>
                <table class="table table-dark" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>SKU</th>
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
                                    <img src="../{{ $p->ViewProductImage }}" alt="" height="40" style="object-fit:cover;">
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
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $p->categories->category_name ?? '-' }}</td>
                            <td>{{ $p->ProductSku }}</td>
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
                                <a href="{{ url('admin/products/'.$p->id.'/edit') }}" class="btn btn-sm btn-primary mb-1">Edit</a>
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
                        <tr>
                            <td colspan="9" class="text-center">No vendor products yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center">{{ $products->withQueryString()->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
