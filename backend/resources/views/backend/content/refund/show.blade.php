@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }}-Refund Claim
@endsection

@php
    $statusColors = [
        'pending' => 'warning',
        'in_progress' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'closed' => 'secondary',
    ];
    $productImage = $claim->product?->ViewProductImage;
    $productImageUrl = $productImage
        ? (\Illuminate\Support\Str::startsWith($productImage, ['http://', 'https://']) ? $productImage : asset($productImage))
        : null;
@endphp

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.refunds.index') }}">Refund Claims</a></li>
                <li class="breadcrumb-item active">{{ $claim->claim_number }}</li>
            </ol>
        </nav>
    </div>

    @if(session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">{{ $claim->claim_number }}</h6>
                    <span class="badge bg-{{ $statusColors[$claim->status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $claim->status)) }}</span>
                </div>
                <div class="admin-card-body">
                    <div class="d-flex gap-3 align-items-start mb-3">
                        @if($productImageUrl)
                            <img src="{{ $productImageUrl }}" alt="Product" style="width:82px;height:82px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                        @endif
                        <div>
                            <h6 class="mb-1">{{ $claim->orderproduct?->productName ?? $claim->product?->ProductName ?? 'Product' }}</h6>
                            <div class="text-muted small">Invoice: {{ $claim->order?->invoiceID ?? 'N/A' }}</div>
                            <div class="text-muted small">Quantity: {{ $claim->orderproduct?->quantity ?? 1 }}</div>
                            @if($claim->orderproduct?->color || $claim->orderproduct?->size)
                                <div class="text-muted small">Variant: {{ $claim->orderproduct?->color }} {{ $claim->orderproduct?->size }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Delivered</label>
                            <div>{{ optional($claim->delivery_date)->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Warranty</label>
                            <div>{{ $claim->warranty_days }} days</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Expires</label>
                            <div>{{ optional($claim->expires_at)->format('d M Y h:i A') }}</div>
                        </div>
                    </div>

                    <label class="form-label">Original Claim Message</label>
                    <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e5e7eb;white-space:pre-wrap">{{ $claim->message }}</div>
                    @if($claim->image_path)
                        <div class="mt-3">
                            <label class="form-label">Claim Image</label>
                            <div>
                                <a href="{{ $claim->image_path }}" target="_blank">
                                    <img src="{{ $claim->image_path }}" alt="Claim image" style="max-width:220px;max-height:180px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title"><i class="bi bi-chat-left-text me-2"></i>Conversation</h6>
                    <span class="badge bg-primary">{{ $claim->messages->count() }} messages</span>
                </div>
                <div class="admin-card-body">
                    @forelse($claim->messages as $message)
                        <div class="mb-3 p-3 rounded" style="border:1px solid #e5e7eb;background:{{ $message->sender_type === 'admin' ? '#f0f9ff' : '#fff' }}">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <strong>{{ $message->sender_type === 'admin' ? ($message->admin?->name ?? 'Admin') : ($message->user?->name ?? 'Reseller') }}</strong>
                                <span class="text-muted small">{{ optional($message->created_at)->format('d M Y h:i A') }}</span>
                            </div>
                            <div style="white-space:pre-wrap">{{ $message->message }}</div>
                            @if($message->attachment_path)
                                <div class="mt-2">
                                    <a href="{{ $message->attachment_path }}" target="_blank">
                                        <img src="{{ $message->attachment_path }}" alt="Attachment" style="max-width:160px;max-height:120px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted">No messages yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Reseller</h6>
                </div>
                <div class="admin-card-body">
                    <div class="mb-2"><strong>{{ $claim->user?->name ?? 'Reseller' }}</strong></div>
                    <div class="text-muted mb-2">{{ $claim->user?->phone ?? 'No phone' }}</div>
                    <div class="text-muted mb-3">{{ $claim->user?->email ?? 'No email' }}</div>
                    <label class="form-label">Account Number</label>
                    @if($payoutAccount)
                        <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e5e7eb">
                            <div><strong>{{ $payoutAccount['label'] ?: 'Account' }}</strong></div>
                            <div>{{ $payoutAccount['number'] }}</div>
                            @if($payoutAccount['name'])
                                <div class="text-muted small">{{ $payoutAccount['name'] }}</div>
                            @endif
                            <div class="text-muted small">{{ $payoutAccount['source'] }}</div>
                        </div>
                    @else
                        <div class="text-muted">No payout account found.</div>
                    @endif
                </div>
            </div>

            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Update Status</h6>
                </div>
                <div class="admin-card-body">
                    <form action="{{ route('admin.refunds.status', $claim) }}" method="POST">
                        @csrf
                        <select name="status" class="form-select mb-3" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ $claim->status === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary w-100" type="submit">Update Status</button>
                    </form>
                </div>
            </div>

            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Send Reply</h6>
                </div>
                <div class="admin-card-body">
                    <form action="{{ route('admin.refunds.reply', $claim) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image <span class="text-muted">(optional)</span></label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-send me-1"></i>Send Reply
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
