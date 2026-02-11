@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Requests
@endsection

@section('maincontent')
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <h6 class="mb-0">Vendor Requests</h6>
                <p class="text-muted small mb-0">Approve or reject vendor registrations. Approved vendors can sign in at the vendor portal.</p>
            </div>
        </div>
        @if(session('message'))
        <div class="col-12">
            <div class="alert alert-success">{{ session('message') }}</div>
        </div>
        @endif
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 rounded bg-secondary h-100 mt-2">
                <table class="table table-dark" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>User (email)</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                        <tr>
                            <td>{{ $vendor->id }}</td>
                            <td>{{ $vendor->company_name }}</td>
                            <td>{{ $vendor->contact_name ?? $vendor->contact_email ?? '-' }}</td>
                            <td>{{ $vendor->user->email ?? '-' }}</td>
                            <td>
                                @if($vendor->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($vendor->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td>{{ $vendor->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($vendor->status === 'pending')
                                <form action="{{ url('admin/vendors/'.$vendor->id.'/approve') }}" method="post" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form action="{{ url('admin/vendors/'.$vendor->id.'/reject') }}" method="post" class="d-inline">
                                    @csrf
                                    <input type="text" name="reason" placeholder="Reason (optional)" class="form-control form-control-sm d-inline-block w-auto">
                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No vendor registrations yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center">{{ $vendors->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
