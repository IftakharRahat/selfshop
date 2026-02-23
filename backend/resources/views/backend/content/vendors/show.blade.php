@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Vendor Details
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendor Requests</a></li>
                <li class="breadcrumb-item active">{{ $vendor->company_name }}</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-md-8">
            {{-- Company Info --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-body">
                    <h5 class="mb-1" style="font-weight: 700;">{{ $vendor->company_name }}</h5>
                    <p class="mb-1 small text-muted">
                        Vendor ID: #{{ $vendor->id }} &middot;
                        User ID: #{{ $vendor->user->id ?? '-' }}
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
                    <p class="mb-1 small text-muted">
                        Verified badge:
                        @if($vendor->is_verified_badge)
                            <span class="badge bg-primary">Verified</span>
                        @else
                            <span class="badge" style="background: #e2e8f0; color: #64748b;">Not verified</span>
                        @endif
                    </p>
                    <p class="mb-0 small text-muted">
                        Registered at: {{ $vendor->created_at?->format('Y-m-d H:i') ?? '-' }}
                    </p>
                </div>
            </div>

            {{-- Business & Contact --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Business & Contact</h6>
                </div>
                <div class="admin-card-body">
                    <div class="row small">
                        <div class="col-md-6 mb-2"><strong>Business type:</strong><br>{{ $vendor->business_type ?? '-' }}</div>
                        <div class="col-md-6 mb-2"><strong>Slug:</strong><br>{{ $vendor->slug ?? '-' }}</div>
                        <div class="col-md-6 mb-2"><strong>Contact person:</strong><br>{{ $vendor->contact_name ?? '-' }}</div>
                        <div class="col-md-6 mb-2"><strong>Contact email:</strong><br>{{ $vendor->contact_email ?? ($vendor->user->email ?? '-') }}</div>
                        <div class="col-md-6 mb-2"><strong>Contact phone:</strong><br>{{ $vendor->contact_phone ?? ($vendor->user->phone ?? '-') }}</div>
                        <div class="col-md-6 mb-2"><strong>Country / City:</strong><br>{{ $vendor->country ?? '-' }} {{ $vendor->city ? ' / '.$vendor->city : '' }}</div>
                        <div class="col-md-12 mb-2"><strong>Address:</strong><br>{{ $vendor->address_line_1 ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Linked User --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Linked User</h6>
                </div>
                <div class="admin-card-body">
                    @if($vendor->user)
                        <p class="small mb-1"><strong>Name:</strong> {{ $vendor->user->name }}</p>
                        <p class="small mb-1"><strong>Email:</strong> {{ $vendor->user->email }}</p>
                        <p class="small mb-1"><strong>Phone:</strong> {{ $vendor->user->phone }}</p>
                        <p class="small mb-1"><strong>User status:</strong> {{ $vendor->user->status ?? '-' }}</p>
                        <p class="small mb-0">
                            <strong>Wholesale access:</strong>
                            @if($vendor->user->is_verified_wholesaler)
                                <span class="badge bg-success">Enabled</span>
                            @else
                                <span class="badge" style="background: #e2e8f0; color: #64748b;">Disabled</span>
                            @endif
                        </p>
                    @else
                        <p class="small mb-0 text-muted">No linked user record found.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Admin Actions --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Admin Actions</h6>
                </div>
                <div class="admin-card-body">
                    @if($vendor->status === 'pending')
                        <form action="{{ route('admin.vendors.approve', $vendor->id) }}" method="post" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success w-100">Approve vendor</button>
                        </form>
                        <form action="{{ route('admin.vendors.reject', $vendor->id) }}" method="post">
                            @csrf
                            <input type="text" name="reason" placeholder="Reason (optional)" class="form-control form-control-sm mb-2">
                            <button type="submit" class="btn btn-sm btn-danger w-100">Reject vendor</button>
                        </form>
                    @elseif($vendor->status === 'approved')
                        @if($vendor->is_verified_badge)
                            <form action="{{ route('admin.vendors.remove-verified-badge', $vendor->id) }}" method="post">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning w-100">Remove verified badge</button>
                            </form>
                            @if($vendor->verified_badge_at)
                                <p class="small text-muted mt-2 mb-0">Badge granted at {{ $vendor->verified_badge_at->format('Y-m-d H:i') }}.</p>
                            @endif
                        @else
                            <form action="{{ route('admin.vendors.verify-badge', $vendor->id) }}" method="post">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info w-100">Give verified badge</button>
                            </form>
                        @endif
                    @else
                        <p class="small mb-0 text-muted">Badge actions are unavailable while vendor status is rejected.</p>
                    @endif
                </div>
            </div>

            {{-- KYC Documents --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">KYC Documents</h6>
                </div>
                <div class="admin-card-body">
                    @if($vendor->kycDocuments->isEmpty())
                        <p class="small mb-0 text-muted">No KYC documents submitted yet.</p>
                    @else
                        @foreach($vendor->kycDocuments as $doc)
                            <div class="mb-2 pb-2" style="border-bottom: 1px solid var(--admin-border, #f1f5f9);">
                                <strong class="small">{{ $doc->document_type }}</strong>
                                @if($doc->document_number)
                                    <span class="small text-muted"> - {{ $doc->document_number }}</span>
                                @endif
                                <br>
                                <span class="small">Status:
                                    @if($doc->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($doc->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </span>
                                <br><small class="text-muted">{{ $doc->created_at?->format('Y-m-d H:i') }}</small>
                                @if($doc->document_path)
                                    <br><a href="{{ asset('storage/'.$doc->document_path) }}" target="_blank" class="small" style="color: var(--admin-primary, #2d2a5d);">View file</a>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Warehouses --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Warehouses</h6>
                </div>
                <div class="admin-card-body">
                    @if($vendor->warehouses->isEmpty())
                        <p class="small mb-0 text-muted">No warehouse locations configured yet.</p>
                    @else
                        @foreach($vendor->warehouses as $wh)
                            <div class="mb-2 pb-2 small" style="border-bottom: 1px solid var(--admin-border, #f1f5f9);">
                                <strong>{{ $wh->label ?? 'Warehouse #'.$wh->id }}</strong><br>
                                {{ $wh->address_line_1 ?? '' }}<br>
                                {{ $wh->city ?? '' }} {{ $wh->postcode ?? '' }}
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Payout Accounts --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Payout Accounts</h6>
                </div>
                <div class="admin-card-body">
                    @if($vendor->payoutAccounts->isEmpty())
                        <p class="small mb-0 text-muted">No payout/bank accounts added yet.</p>
                    @else
                        @foreach($vendor->payoutAccounts as $acct)
                            <div class="mb-2 pb-2 small" style="border-bottom: 1px solid var(--admin-border, #f1f5f9);">
                                <strong>{{ $acct->label ?? 'Account #'.$acct->id }}</strong><br>
                                {{ $acct->provider ?? '' }} {{ $acct->account_number ?? '' }}
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
