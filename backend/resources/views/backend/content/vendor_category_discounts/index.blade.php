@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Supplier Category Discounts
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Supplier Category Discounts</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Supplier Category Discounts</h6>
            <p class="mb-0" style="font-size: 12px; color: #64748b;">Discounts set by suppliers per category. Suppliers manage these from their portal.</p>
        </div>
        <div class="admin-card-body">
            <form method="get" class="row g-2 mb-0">
                <div class="col-auto">
                    <input type="number" name="vendor_id" class="form-control form-control-sm" placeholder="Supplier ID" value="{{ request('vendor_id') }}">
                </div>
                <div class="col-auto">
                    <input type="number" name="category_id" class="form-control form-control-sm" placeholder="Category ID" value="{{ request('category_id') }}">
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
                            <th>Supplier</th>
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
                                {{ $d->vendor->company_name ?? 'Supplier #'.$d->vendor_id }}
                                @if($d->vendor && $d->vendor->user)
                                    <br><small class="text-muted">{{ $d->vendor->user->email }}</small>
                                @endif
                            </td>
                            <td>{{ $d->category->category_name ?? 'Category #'.$d->category_id }}</td>
                            <td><strong>{{ $d->discount_percent }}%</strong></td>
                            <td>{{ $d->start_date ? $d->start_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $d->end_date ? $d->end_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $d->updated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No supplier category discounts set yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card-body d-flex justify-content-center">{{ $discounts->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
