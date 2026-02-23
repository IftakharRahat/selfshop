@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Category Commission
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Vendor Category Commission</li>
            </ol>
        </nav>
    </div>

    @if(session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Vendor Category Commission</h6>
            <p class="mb-0" style="font-size: 12px; color: #64748b;">
                Set global commission per category. Default: {{ number_format($globalDefault, 2) }}%.
            </p>
        </div>
        <div class="admin-card-body">
            <form method="get" action="{{ route('admin.vendor-category-commissions.index') }}" class="row g-2 mb-0">
                <div class="col-sm-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search category" value="{{ $search }}">
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-sm w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Filter</button>
                </div>
                <div class="col-sm-2">
                    <a href="{{ route('admin.vendor-category-commissions.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
        <div class="admin-card-body p-0" style="border-top: 1px solid var(--admin-border, #f1f5f9);">
            <div class="table-responsive">
                <table class="table admin-table mb-0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Slug</th>
                            <th>Commission (%)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->id }}</td>
                                <td><strong>{{ $category->category_name }}</strong></td>
                                <td>{{ $category->slug }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.vendor-category-commissions.update', $category->id) }}" class="d-flex gap-2 align-items-center">
                                        @csrf
                                        <input type="number" name="commission_percent" min="0" max="100" step="0.01" class="form-control form-control-sm" value="{{ number_format((float) ($commissionRows[$category->id] ?? $globalDefault), 2, '.', '') }}" required style="max-width: 100px;">
                                        <button type="submit" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Save</button>
                                    </form>
                                    @error('commission_percent')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </td>
                                <td>
                                    <span class="badge" style="background: var(--admin-primary-lighter, #eef2ff); color: var(--admin-primary, #2d2a5d); font-size: 11px;">
                                        {{ array_key_exists($category->id, $commissionRows->toArray()) ? 'Category specific' : 'Using default' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No active categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
