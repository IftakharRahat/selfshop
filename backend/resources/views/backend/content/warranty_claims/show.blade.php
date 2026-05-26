@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }} - Claim #{{ $claim->claim_number }}
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('admin/warranty-claims/all') }}">Refund Claims</a></li>
                <li class="breadcrumb-item active">{{ $claim->claim_number }}</li>
            </ol>
        </nav>
    </div>

    @if (Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>
            {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle me-1"></i>
            {{ Session::get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        {{-- Left column: Info cards --}}
        <div class="col-lg-8">
            {{-- Claim Status --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header d-flex align-items-center justify-content-between">
                    <h6 class="admin-card-title mb-0">Claim #{{ $claim->claim_number }}</h6>
                    @php
                        $badgeClass = match($claim->status) {
                            'pending' => 'bg-warning text-dark',
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} fs-6">{{ ucfirst($claim->status) }}</span>
                </div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <strong>Submitted:</strong> {{ $claim->created_at->format('d M Y, h:i A') }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Warranty:</strong> {{ $claim->warranty_days }} days
                            @if($claim->status === 'pending')
                                @php
                                    $daysLeftColor = $daysLeft > 10 ? 'text-success' : ($daysLeft > 5 ? 'text-warning' : 'text-danger');
                                @endphp
                                <span class="fw-bold {{ $daysLeftColor }}">({{ $daysLeft }} days left)</span>
                            @endif
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Delivered:</strong> {{ $claim->delivered_at->format('d M Y') }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Warranty Expires:</strong> {{ $claim->warranty_expires_at->format('d M Y') }}
                        </div>
                    </div>
                    @if($claim->responded_at)
                        <hr>
                        <div class="row">
                            <div class="col-sm-6 mb-2">
                                <strong>Responded At:</strong> {{ $claim->responded_at->format('d M Y, h:i A') }}
                            </div>
                            <div class="col-sm-6 mb-2">
                                <strong>Admin Note:</strong> {{ $claim->admin_note ?? '—' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reseller Info --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title mb-0"><i class="bi bi-person me-1"></i> Reseller Information</h6>
                </div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <strong>Name:</strong> {{ $claim->user->name ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Phone:</strong> {{ $claim->user->phone ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Email:</strong> {{ $claim->user->email ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>User ID:</strong> {{ $claim->user->id ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order Info --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title mb-0"><i class="bi bi-bag me-1"></i> Order Details</h6>
                </div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <strong>Invoice:</strong>
                            <a href="{{ url('admin_order/view/' . $claim->order->id) }}">
                                {{ $claim->order->invoiceID ?? '—' }}
                            </a>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Order Date:</strong> {{ $claim->order->orderDate ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Delivery Date:</strong> {{ $claim->delivered_at->format('d M Y') }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Order Status:</strong> {{ $claim->order->status ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Sub Total:</strong> ৳{{ number_format($claim->order->subTotal ?? 0) }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Customer:</strong> {{ $claim->order->customer->customerName ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Info --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title mb-0"><i class="bi bi-box-seam me-1"></i> Product Details</h6>
                </div>
                <div class="admin-card-body">
                    <div class="d-flex gap-3 align-items-start">
                        @if($claim->product && $claim->product->ViewProductImage)
                            <img src="{{ asset($claim->product->ViewProductImage) }}"
                                 alt="{{ $claim->product->ProductName }}"
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                        @endif
                        <div>
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <strong>Product:</strong> {{ $claim->product->ProductName ?? ($claim->orderProduct->productName ?? '—') }}
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <strong>Code:</strong> {{ $claim->product->ProductSku ?? ($claim->orderProduct->productCode ?? '—') }}
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <strong>Price:</strong> ৳{{ number_format($claim->orderProduct->productPrice ?? 0) }}
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <strong>Quantity:</strong> {{ $claim->orderProduct->quantity ?? '—' }}
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <strong>Warranty:</strong> {{ $claim->warranty_days }} days
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Supplier Info --}}
            @if($claim->vendor)
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title mb-0"><i class="bi bi-truck me-1"></i> Supplier Details</h6>
                </div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <strong>Company:</strong> {{ $claim->vendor->company_name ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Contact:</strong> {{ $claim->vendor->contact_name ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Phone:</strong> {{ $claim->vendor->contact_phone ?? '—' }}
                        </div>
                        <div class="col-sm-6 mb-2">
                            <strong>Email:</strong> {{ $claim->vendor->contact_email ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Claim Reason & Images --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title mb-0"><i class="bi bi-chat-left-text me-1"></i> Claim Reason</h6>
                </div>
                <div class="admin-card-body">
                    <p class="mb-3" style="white-space: pre-wrap;">{{ $claim->reason }}</p>

                    @if($claim->images && count($claim->images) > 0)
                        <h6 class="fw-bold mb-2">Attached Images:</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach($claim->images as $img)
                                <a href="{{ asset('storage/' . $img) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $img) }}"
                                         alt="Claim image"
                                         style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer;">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted"><em>No images attached.</em></p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right column: Action --}}
        <div class="col-lg-4">
            @if($claim->status === 'pending')
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title mb-0"><i class="bi bi-check2-square me-1"></i> Take Action</h6>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="{{ route('admin.warranty-claims.respond', $claim->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Admin Note (optional)</label>
                            <textarea name="admin_note" class="form-control" rows="4" placeholder="Add a note for this claim..."></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="approve"
                                    class="btn btn-success"
                                    onclick="return confirm('Are you sure you want to APPROVE this claim?')">
                                <i class="bi bi-check-circle me-1"></i> Approve Claim
                            </button>
                            <button type="submit" name="action" value="reject"
                                    class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to REJECT this claim?')">
                                <i class="bi bi-x-circle me-1"></i> Reject Claim
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title mb-0"><i class="bi bi-info-circle me-1"></i> Response</h6>
                </div>
                <div class="admin-card-body">
                    <p><strong>Status:</strong>
                        <span class="badge {{ $badgeClass }} fs-6">{{ ucfirst($claim->status) }}</span>
                    </p>
                    @if($claim->admin_note)
                        <p><strong>Note:</strong> {{ $claim->admin_note }}</p>
                    @endif
                    @if($claim->responded_at)
                        <p><strong>Responded:</strong> {{ $claim->responded_at->format('d M Y, h:i A') }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
