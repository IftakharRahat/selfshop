@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Product Reviews
@endsection

@section('maincontent')
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <h6 class="mb-0">Product Reviews</h6>
                <p class="text-muted small mb-0">All customer reviews. You can change status to Active/Inactive.</p>
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
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </div>
                </form>
                <table class="table table-dark" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Vendor</th>
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
                            <td>
                                @if($r->product)
                                    {{ Str::limit($r->product->ProductName, 25) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($r->product && $r->product->vendor)
                                    {{ $r->product->vendor->company_name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $r->user->name ?? $r->user->email ?? '-' }}</td>
                            <td>{{ $r->rating }} ★</td>
                            <td>{{ Str::limit($r->messages, 40) }}</td>
                            <td>
                                @if($r->status === 'Active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
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
                        <tr>
                            <td colspan="9" class="text-center">No reviews found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center">{{ $reviews->withQueryString()->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
