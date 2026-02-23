@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Requests
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Vendor Requests</li>
            </ol>
        </nav>
    </div>

    @if(session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Vendor Requests</h6>
            <p class="mb-0" style="font-size: 12px; color: #64748b;">Approve or reject vendor registrations. Approved vendors can sign in at the vendor portal.</p>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>User (email)</th>
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
                            <td><strong>{{ $vendor->company_name }}</strong></td>
                            <td>{{ $vendor->contact_name ?? $vendor->contact_email ?? '-' }}</td>
                            <td>{{ $vendor->user->email ?? '-' }}</td>
                            <td>
                                @if($vendor->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($vendor->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
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
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No vendor registrations yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card-body d-flex justify-content-center">{{ $vendors->links() }}</div>
    </div>
</div>
@endsection
