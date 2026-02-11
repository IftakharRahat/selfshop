@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Category Discounts
@endsection

@section('maincontent')
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <h6 class="mb-0">Vendor Category Discounts</h6>
                <p class="text-muted small mb-0">Discounts set by vendors per category. Vendors manage these from their portal (Category-Wise Discount).</p>
            </div>
        </div>
        <div class="col-sm-12 col-md-12 col-xl-12 mt-2">
            <div class="p-4 rounded bg-secondary h-100">
                <form method="get" class="mb-3 row g-2">
                    <div class="col-auto">
                        <input type="number" name="vendor_id" class="form-control form-control-sm" placeholder="Vendor ID" value="{{ request('vendor_id') }}">
                    </div>
                    <div class="col-auto">
                        <input type="number" name="category_id" class="form-control form-control-sm" placeholder="Category ID" value="{{ request('category_id') }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </div>
                </form>
                <table class="table table-dark" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Discount %</th>
                            <th>Start date</th>
                            <th>End date</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($discounts as $d)
                        <tr>
                            <td>{{ $d->id }}</td>
                            <td>
                                {{ $d->vendor->company_name ?? 'Vendor #'.$d->vendor_id }}
                                @if($d->vendor && $d->vendor->user)
                                    <br><small class="text-muted">{{ $d->vendor->user->email }}</small>
                                @endif
                            </td>
                            <td>{{ $d->category->category_name ?? 'Category #'.$d->category_id }}</td>
                            <td>{{ $d->discount_percent }}%</td>
                            <td>{{ $d->start_date ? $d->start_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $d->end_date ? $d->end_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $d->updated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No vendor category discounts set yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center">{{ $discounts->withQueryString()->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
