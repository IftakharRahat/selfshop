@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Category Commission
@endsection

@section('maincontent')
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <h6 class="mb-0">Vendor Category Commission</h6>
                <p class="text-muted small mb-0">
                    Set global commission per category for all vendors. If no category-specific value is set, default is {{ number_format($globalDefault, 2) }}%.
                </p>
            </div>
        </div>

        @if(session('message'))
        <div class="col-12 mt-2">
            <div class="alert alert-success">{{ session('message') }}</div>
        </div>
        @endif

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 rounded bg-secondary h-100 mt-2">
                <form method="get" action="{{ route('admin.vendor-category-commissions.index') }}" class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search category" value="{{ $search }}">
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-sm-2">
                        <a href="{{ route('admin.vendor-category-commissions.index') }}" class="btn btn-sm btn-secondary w-100">Reset</a>
                    </div>
                </form>

                <table class="table table-dark" width="100%">
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
                                <td>{{ $category->category_name }}</td>
                                <td>{{ $category->slug }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.vendor-category-commissions.update', $category->id) }}" class="d-flex gap-2 align-items-center">
                                        @csrf
                                        <input
                                            type="number"
                                            name="commission_percent"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="form-control form-control-sm"
                                            value="{{ number_format((float) ($commissionRows[$category->id] ?? $globalDefault), 2, '.', '') }}"
                                            required
                                        >
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                    @error('commission_percent')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">
                                        {{ array_key_exists($category->id, $commissionRows->toArray()) ? 'Category specific' : 'Using default' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No active categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
