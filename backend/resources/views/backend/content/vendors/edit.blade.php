@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Edit Supplier
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Suppliers</a></li>
                <li class="breadcrumb-item active">Edit: {{ $vendor->company_name }}</li>
            </ol>
        </nav>
    </div>

    <form method="POST" action="{{ route('admin.vendors.update', $vendor->id) }}">
        @csrf
        @method('PUT')

        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Edit Supplier Account</h6>
            </div>
            <div class="admin-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $vendor->company_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Type</label>
                        <input type="text" name="business_type" class="form-control" value="{{ old('business_type', $vendor->business_type) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $vendor->contact_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $vendor->contact_email) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $vendor->contact_phone) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control" value="{{ old('country', $vendor->country) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city', $vendor->city) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="pending" @selected(old('status', $vendor->status) === 'pending')>Pending</option>
                            <option value="approved" @selected(old('status', $vendor->status) === 'approved')>Approved</option>
                            <option value="rejected" @selected(old('status', $vendor->status) === 'rejected')>Rejected</option>
                            <option value="suspended" @selected(old('status', $vendor->status) === 'suspended')>Suspended</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Address</label>
                        <input type="text" name="address_line_1" class="form-control" value="{{ old('address_line_1', $vendor->address_line_1) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Linked User Status</label>
                        <select name="user_status" class="form-select">
                            <option value="">Do not change</option>
                            <option value="Active" @selected(old('user_status') === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('user_status') === 'Inactive')>Inactive</option>
                            <option value="Block" @selected(old('user_status') === 'Block')>Block</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Linked User Membership</label>
                        <select name="membership_status" class="form-select">
                            <option value="">Do not change</option>
                            <option value="Paid" @selected(old('membership_status') === 'Paid')>Paid</option>
                            <option value="Unpaid" @selected(old('membership_status') === 'Unpaid')>Unpaid</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="is_verified_badge" name="is_verified_badge" value="1" @checked(old('is_verified_badge', $vendor->is_verified_badge))>
                            <label class="form-check-label" for="is_verified_badge">
                                Verified badge
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Admin Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $vendor->notes) }}</textarea>
                    </div>

                    <div class="col-md-12">
                        @if ($errors->any())
                            <div class="alert alert-danger mb-0">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Supplier</button>
                        <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

