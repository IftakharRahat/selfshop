@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Supplier Details
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Supplier Requests</a></li>
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
                        Supplier ID: #{{ $vendor->id }} &middot;
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
                    <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="btn btn-sm btn-primary w-100 mb-2">Edit supplier account</a>

                    {{-- Show current approval type --}}
                    @if($vendor->status === 'approved' && $vendor->approval_type)
                        <p class="small mb-2">
                            Approval type:
                            @if($vendor->approval_type === 'private')
                                <span class="badge bg-secondary">🔒 Private ({{ $vendor->private_id }})</span>
                            @else
                                <span class="badge bg-info">🌐 Public</span>
                            @endif
                        </p>
                    @endif

                    @if($vendor->status === 'pending')
                        <div class="d-flex gap-2 mb-2">
                            <form action="{{ route('admin.vendors.approve', $vendor->id) }}" method="post" class="flex-fill">
                                @csrf
                                <input type="hidden" name="approval_type" value="public">
                                <button type="submit" class="btn btn-sm btn-success w-100">🌐 Approve (Public)</button>
                            </form>
                            <form action="{{ route('admin.vendors.approve', $vendor->id) }}" method="post" class="flex-fill">
                                @csrf
                                <input type="hidden" name="approval_type" value="private">
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">🔒 Approve (Private)</button>
                            </form>
                        </div>
                        <form action="{{ route('admin.vendors.reject', $vendor->id) }}" method="post">
                            @csrf
                            <input type="text" name="reason" placeholder="Reason (optional)" class="form-control form-control-sm mb-2">
                            <button type="submit" class="btn btn-sm btn-danger w-100">Reject supplier</button>
                        </form>
                    @elseif($vendor->status === 'approved')
                        {{-- Toggle approval type --}}
                        @if($vendor->approval_type === 'private')
                            <form action="{{ route('admin.vendors.approve', $vendor->id) }}" method="post" class="mb-2">
                                @csrf
                                <input type="hidden" name="approval_type" value="public">
                                <button type="submit" class="btn btn-sm btn-outline-info w-100">Switch to Public</button>
                            </form>
                        @else
                            <form action="{{ route('admin.vendors.approve', $vendor->id) }}" method="post" class="mb-2">
                                @csrf
                                <input type="hidden" name="approval_type" value="private">
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Switch to Private</button>
                            </form>
                        @endif

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
                        <p class="small mb-0 text-muted">Badge actions are unavailable while supplier status is rejected.</p>
                    @endif
                </div>
            </div>

            {{-- Pending Branding Changes --}}
            @if($vendor->pending_logo_path || $vendor->pending_banner_path)
            <div class="admin-content-card mb-3" style="border: 2px solid #f59e0b;">
                <div class="admin-card-header" style="background: #fef3c7;">
                    <h6 class="admin-card-title" style="color: #92400e;">
                        Pending Branding Changes
                    </h6>
                </div>
                <div class="admin-card-body">
                    @if($vendor->pending_logo_path)
                    <div class="mb-3 pb-3" style="border-bottom: 1px solid #fde68a;">
                        <p class="small fw-bold mb-2">Logo Change</p>
                        <div class="d-flex gap-3 align-items-start">
                            <div class="text-center">
                                <p class="small text-muted mb-1">Current</p>
                                @if($vendor->logo_path)
                                    <img src="{{ $vendor->logo_path }}" alt="Current logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                                @else
                                    <div style="width: 80px; height: 80px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #94a3b8;">No logo</div>
                                @endif
                            </div>
                            <div style="align-self: center; font-size: 20px; color: #94a3b8;">&rarr;</div>
                            <div class="text-center">
                                <p class="small text-muted mb-1">Pending</p>
                                <img src="{{ $vendor->pending_logo_path }}" alt="Pending logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #f59e0b;">
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <form action="{{ route('admin.vendors.approve-branding', $vendor->id) }}" method="post">
                                @csrf
                                <input type="hidden" name="type" value="logo">
                                <button type="submit" class="btn btn-sm btn-success">Approve Logo</button>
                            </form>
                            <form action="{{ route('admin.vendors.reject-branding', $vendor->id) }}" method="post">
                                @csrf
                                <input type="hidden" name="type" value="logo">
                                <button type="submit" class="btn btn-sm btn-danger">Reject Logo</button>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if($vendor->pending_banner_path)
                    <div class="mb-1">
                        <p class="small fw-bold mb-2">Banner/Cover Change</p>
                        <div class="mb-2">
                            <p class="small text-muted mb-1">Current</p>
                            @if($vendor->banner_path)
                                <img src="{{ $vendor->banner_path }}" alt="Current banner" style="width: 100%; max-height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                            @else
                                <div style="width: 100%; height: 60px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #94a3b8;">No banner</div>
                            @endif
                        </div>
                        <div>
                            <p class="small text-muted mb-1">Pending</p>
                            <img src="{{ $vendor->pending_banner_path }}" alt="Pending banner" style="width: 100%; max-height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #f59e0b;">
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <form action="{{ route('admin.vendors.approve-branding', $vendor->id) }}" method="post">
                                @csrf
                                <input type="hidden" name="type" value="banner">
                                <button type="submit" class="btn btn-sm btn-success">Approve Banner</button>
                            </form>
                            <form action="{{ route('admin.vendors.reject-branding', $vendor->id) }}" method="post">
                                @csrf
                                <input type="hidden" name="type" value="banner">
                                <button type="submit" class="btn btn-sm btn-danger">Reject Banner</button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

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
                            <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--admin-border, #f1f5f9);">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
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
                                        @if($doc->review_notes)
                                            <br><small class="text-danger">Notes: {{ $doc->review_notes }}</small>
                                        @endif
                                    </div>
                                    @if($doc->document_path)
                                        <a href="{{ $doc->document_path }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 11px;">View file</a>
                                    @endif
                                </div>

                                {{-- Approve / Reject actions --}}
                                @if($doc->status !== 'approved')
                                    <div class="d-flex gap-2 mt-2">
                                        <form action="{{ route('admin.vendors.approve-kyc', $doc->id) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        @if($doc->status !== 'rejected')
                                            <form action="{{ route('admin.vendors.reject-kyc', $doc->id) }}" method="post" class="d-flex gap-1">
                                                @csrf
                                                <input type="text" name="review_notes" placeholder="Reason (optional)" class="form-control form-control-sm" style="width: 160px;">
                                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <div class="mt-2">
                                        <small class="text-muted">Verified {{ $doc->verified_at?->format('Y-m-d H:i') }}</small>
                                    </div>
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
