@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Supplier Requests
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Supplier Requests</li>
            </ol>
        </nav>
    </div>


    <div class="row g-3 mb-3">
        <div class="col-md-2">
            <a href="{{ route('admin.vendors.index') }}" class="text-decoration-none">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center">
                        <div class="small text-muted">All</div>
                        <div style="font-size: 20px; font-weight: 700;">{{ number_format($summary['all'] ?? 0) }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="text-decoration-none">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center">
                        <div class="small text-muted">Active</div>
                        <div style="font-size: 20px; font-weight: 700;">{{ number_format($summary['approved'] ?? 0) }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center">
                        <div class="small text-muted">Pending</div>
                        <div style="font-size: 20px; font-weight: 700;">{{ number_format($summary['pending'] ?? 0) }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.vendors.index', ['status' => 'rejected']) }}" class="text-decoration-none">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center">
                        <div class="small text-muted">Rejected</div>
                        <div style="font-size: 20px; font-weight: 700;">{{ number_format($summary['rejected'] ?? 0) }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.vendors.index', ['status' => 'suspended']) }}" class="text-decoration-none">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center">
                        <div class="small text-muted">Suspended</div>
                        <div style="font-size: 20px; font-weight: 700;">{{ number_format($summary['suspended'] ?? 0) }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.vendors.index', ['branding' => 'pending']) }}" class="text-decoration-none">
                <div class="admin-content-card" style="{{ request('branding') === 'pending' ? 'border: 2px solid #f59e0b;' : '' }}">
                    <div class="admin-card-body text-center">
                        <div class="small text-muted">Pending Branding</div>
                        <div style="font-size: 20px; font-weight: 700; color: #f59e0b;">{{ number_format($summary['pending_branding'] ?? 0) }}</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Supplier Requests</h6>
            <p class="mb-0" style="font-size: 12px; color: #64748b;">Approve or reject supplier registrations. Approved suppliers can sign in at the supplier portal.</p>
        </div>
        <div class="admin-card-body">
            <form method="get" class="row g-2 mb-0">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All status</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search company, contact or email" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff;">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.vendors.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Supplier ID</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>User (Email)</th>
                            <th>Status</th>
                            <th>Badge</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                        <tr>
                            <td>{{ $vendor->id }}</td>
                            <td>
                                <a href="{{ url('admin/vendor-autologin/' . $vendor->id) }}"
                                   target="_blank" style="color:#2d2a5d; font-weight:600;">
                                    {{ $vendor->supplier_code }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $vendor->company_name }}</strong>
                                @if($vendor->pending_logo_path || $vendor->pending_banner_path)
                                    <a href="{{ route('admin.vendors.show', $vendor->id) }}" class="badge bg-warning text-dark ms-1" style="font-size: 10px; text-decoration: none;" title="Pending branding changes">Pending</a>
                                @endif
                            </td>
                            <td>{{ $vendor->contact_phone ?? $vendor->user->phone ?? '-' }}</td>
                            <td>{{ $vendor->user->name ?? $vendor->user->email ?? '-' }}</td>
                            <td>
                                @if($vendor->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($vendor->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($vendor->status === 'suspended')
                                    <span class="badge bg-secondary">Suspended</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td>
                                @if($vendor->is_verified_badge)
                                    <span class="badge bg-primary">Verified</span>
                                @else
                                    <span class="badge" style="background: #e2e8f0; color: #64748b;">Not verified</span>
                                @endif
                            </td>
                            <td>{{ $vendor->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.vendors.show', $vendor->id) }}" class="btn btn-sm btn-outline-primary mb-1">View</a>
                                <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="btn btn-sm btn-primary mb-1">Edit</a>
                                @if($vendor->status === 'pending')
                                    <form action="{{ url('admin/vendors/'.$vendor->id.'/approve') }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success mb-1">Approve</button>
                                    </form>
                                    <form action="{{ url('admin/vendors/'.$vendor->id.'/reject') }}" method="post" class="d-inline mt-1">
                                        @csrf
                                        <input type="text" name="reason" placeholder="Reason (optional)" class="form-control form-control-sm d-inline-block w-auto mb-1">
                                        <button type="submit" class="btn btn-sm btn-danger mb-1">Reject</button>
                                    </form>
                                @elseif($vendor->status === 'approved')
                                    @if($vendor->is_verified_badge)
                                        <form action="{{ route('admin.vendors.remove-verified-badge', $vendor->id) }}" method="post" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning mb-1">Remove badge</button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.vendors.verify-badge', $vendor->id) }}" method="post" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-info mb-1">Give verified badge</button>
                                        </form>
                                    @endif
                                @endif
                                <form action="{{ route('admin.vendors.destroy', $vendor->id) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete {{ addslashes($vendor->company_name) }} and all their data? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger mb-1">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No supplier registrations yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card-body d-flex justify-content-center">{{ $vendors->links('vendor.pagination.admin') }}</div>
    </div>
</div>
@endsection
