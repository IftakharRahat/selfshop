@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Details
@endsection

@section('maincontent')
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-12 mb-3">
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-sm btn-secondary">&larr; Back to vendor list</a>
        </div>

        <div class="col-md-8">
            <div class="p-4 mb-3 rounded bg-white border">
                <h5 class="mb-1">{{ $vendor->company_name }}</h5>
                <p class="mb-1 small text-muted">
                    Vendor ID: #{{ $vendor->id }} &middot;
                    User ID: #{{ $vendor->user->id ?? '—' }}
                </p>
                <p class="mb-1 small text-muted">
                    Status:
                    @if($vendor->status === 'pending')
                        <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($vendor->status === 'approved')
                        <span class="badge bg-success">Approved</span>
                    @else
                        <span class="badge bg-danger">Rejected</span>
                    @endif
                </p>
                <p class="mb-0 small text-muted">
                    Registered at: {{ $vendor->created_at?->format('Y-m-d H:i') ?? '—' }}
                </p>
            </div>

            <div class="p-4 mb-3 rounded bg-white border">
                <h6 class="mb-3">Business & contact</h6>
                <div class="row small text-muted">
                    <div class="col-md-6 mb-2">
                        <strong>Business type:</strong><br>
                        {{ $vendor->business_type ?? '—' }}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Slug:</strong><br>
                        {{ $vendor->slug ?? '—' }}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Contact person:</strong><br>
                        {{ $vendor->contact_name ?? '—' }}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Contact email:</strong><br>
                        {{ $vendor->contact_email ?? ($vendor->user->email ?? '—') }}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Contact phone:</strong><br>
                        {{ $vendor->contact_phone ?? ($vendor->user->phone ?? '—') }}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Country / City:</strong><br>
                        {{ $vendor->country ?? '—' }} {{ $vendor->city ? ' / '.$vendor->city : '' }}
                    </div>
                    <div class="col-md-12 mb-2">
                        <strong>Address:</strong><br>
                        {{ $vendor->address_line_1 ?? '—' }}
                    </div>
                </div>
            </div>

            <div class="p-4 mb-3 rounded bg-white border">
                <h6 class="mb-3">Linked user</h6>
                @if($vendor->user)
                    <p class="small mb-1"><strong>Name:</strong> {{ $vendor->user->name }}</p>
                    <p class="small mb-1"><strong>Email:</strong> {{ $vendor->user->email }}</p>
                    <p class="small mb-1"><strong>Phone:</strong> {{ $vendor->user->phone }}</p>
                    <p class="small mb-0"><strong>User status:</strong> {{ $vendor->user->status ?? '—' }}</p>
                @else
                    <p class="small mb-0">No linked user record found.</p>
                @endif
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-4 mb-3 rounded bg-white border">
                <h6 class="mb-3">KYC documents</h6>
                @if($vendor->kycDocuments->isEmpty())
                    <p class="small mb-0">No KYC documents submitted yet.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($vendor->kycDocuments as $doc)
                            <li class="list-group-item small">
                                <strong>{{ $doc->document_type }}</strong>
                                @if($doc->document_number)
                                    <span class="text-light"> • {{ $doc->document_number }}</span>
                                @endif
                                <br>
                                Status:
                                @if($doc->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($doc->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                                <br>
                                <span class="text-muted">
                                    {{ $doc->created_at?->format('Y-m-d H:i') }}
                                </span>
                                @if($doc->document_path)
                                    <br>
                                    <a href="{{ asset('storage/'.$doc->document_path) }}" target="_blank" class="text-info">
                                        View file
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="p-4 mb-3 rounded bg-white border">
                <h6 class="mb-3">Warehouses</h6>
                @if($vendor->warehouses->isEmpty())
                    <p class="small mb-0">No warehouse locations configured yet.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($vendor->warehouses as $wh)
                            <li class="list-group-item small">
                                <strong>{{ $wh->label ?? 'Warehouse #'.$wh->id }}</strong><br>
                                {{ $wh->address_line_1 ?? '' }}<br>
                                {{ $wh->city ?? '' }} {{ $wh->postcode ?? '' }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="p-4 mb-3 rounded bg-white border">
                <h6 class="mb-3">Payout accounts</h6>
                @if($vendor->payoutAccounts->isEmpty())
                    <p class="small mb-0">No payout/bank accounts added yet.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($vendor->payoutAccounts as $acct)
                            <li class="list-group-item small">
                                <strong>{{ $acct->label ?? 'Account #'.$acct->id }}</strong><br>
                                {{ $acct->provider ?? '' }} {{ $acct->account_number ?? '' }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

