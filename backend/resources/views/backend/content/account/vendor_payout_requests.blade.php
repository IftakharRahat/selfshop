@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }} - Supplier Payout Requests
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Supplier Payout Requests</li>
            </ol>
        </nav>
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-md-6 mb-2">
            <a href="{{ url('admin/view-vendor-payout-requests/pending') }}">
                <div class="admin-content-card {{ ($currentStatus ?? '') === 'pending' ? 'border-start border-primary border-3' : '' }}">
                    <div class="admin-card-body text-center py-3">
                        <p class="mb-1" style="color: #6c757d; font-size: 13px;">Pending</p>
                        <h4 style="color: #ffc107; font-weight: 700;">{{ $counts['pending'] ?? 0 }}</h4>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <a href="{{ url('admin/view-vendor-payout-requests/approved') }}">
                <div class="admin-content-card {{ ($currentStatus ?? '') === 'approved' ? 'border-start border-success border-3' : '' }}">
                    <div class="admin-card-body text-center py-3">
                        <p class="mb-1" style="color: #6c757d; font-size: 13px;">Approved</p>
                        <h4 style="color: #198754; font-weight: 700;">{{ $counts['approved'] ?? 0 }}</h4>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <a href="{{ url('admin/view-vendor-payout-requests/rejected') }}">
                <div class="admin-content-card {{ ($currentStatus ?? '') === 'rejected' ? 'border-start border-danger border-3' : '' }}">
                    <div class="admin-card-body text-center py-3">
                        <p class="mb-1" style="color: #6c757d; font-size: 13px;">Rejected</p>
                        <h4 style="color: #dc3545; font-weight: 700;">{{ $counts['rejected'] ?? 0 }}</h4>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <a href="{{ url('admin/view-vendor-payout-requests') }}">
                <div class="admin-content-card {{ ($currentStatus ?? null) === null ? 'border-start border-secondary border-3' : '' }}">
                    <div class="admin-card-body text-center py-3">
                        <p class="mb-1" style="color: #6c757d; font-size: 13px;">All</p>
                        <h4 style="color: var(--admin-primary, #2d2a5d); font-weight: 700;">{{ ($counts['pending'] ?? 0) + ($counts['approved'] ?? 0) + ($counts['rejected'] ?? 0) }}</h4>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Payout Requests</h6>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $ind => $req)
                            <tr>
                                <td>{{ $requests->firstItem() + $ind }}</td>
                                <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    {{ $req->vendor->company_name ?? '—' }}<br>
                                    <small class="text-muted">{{ $req->vendor->contact_email ?? '' }}</small>
                                </td>
                                <td>
                                    @php
                                        $acct = $req->payoutAccount
                                            ?? $req->vendor?->payoutAccounts?->firstWhere('is_default', true)
                                            ?? $req->vendor?->payoutAccounts?->first();
                                    @endphp
                                    @if($acct)
                                        <strong class="small">{{ ucfirst($acct->channel_type) }}</strong>
                                        @if($acct->provider_name)
                                            <span class="small text-muted">- {{ $acct->provider_name }}</span>
                                        @endif
                                        <br>
                                        <small class="text-muted">{{ $acct->account_name }}</small><br>
                                        <small class="fw-bold">{{ $acct->account_number }}</small>
                                        @if($acct->routing_number)
                                            <br><small class="text-muted">Routing: {{ $acct->routing_number }}</small>
                                        @endif
                                        @if(!$req->payoutAccount)
                                            <br><span class="badge bg-warning text-dark" style="font-size:10px;">default</span>
                                        @endif
                                    @else
                                        <span class="text-danger small">No account</span>
                                    @endif
                                </td>
                                <td>৳{{ number_format((float)$req->amount, 2) }}</td>
                                <td>
                                    @if($req->status === 'pending')
                                        <span class="badge bg-info">pending</span>
                                    @elseif($req->status === 'approved')
                                        <span class="badge bg-success">approved</span>
                                    @else
                                        <span class="badge bg-danger">rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.vendors.sales-summary', $req->vendor_id) }}" class="btn btn-outline-primary btn-sm" title="View supplier details">View</a>
                                    @if($req->status === 'pending')
                                        <button type="button" class="btn btn-success btn-sm btn-approve" data-id="{{ $req->id }}">Approve</button>
                                        <button type="button" class="btn btn-danger btn-sm btn-reject" data-id="{{ $req->id }}">Reject</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4">No payout requests.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $requests->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

{{-- Reject modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight: 600;">Reject payout request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject-id">
                <label class="form-label">Note (optional)</label>
                <textarea id="reject-notes" class="form-control" rows="2" placeholder="Admin notes"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="reject-confirm">Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('meta[name="csrf_token"]')?.getAttribute('content');
    const base = '{{ url("admin/vendor-payout-requests") }}';

    document.querySelectorAll('.btn-approve').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (!confirm('Approve this payout request?')) return;
            fetch(base + '/' + id + '/approve', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: '{}'
            }).then(r => r.json()).then(function(data) {
                if (data.status) { alert('Approved.'); location.reload(); }
                else { alert(data.message || 'Failed'); }
            }).catch(function() { alert('Request failed'); });
        });
    });

    document.querySelectorAll('.btn-reject').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('reject-id').value = this.getAttribute('data-id');
            document.getElementById('reject-notes').value = '';
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        });
    });

    document.getElementById('reject-confirm').addEventListener('click', function() {
        const id = document.getElementById('reject-id').value;
        const notes = document.getElementById('reject-notes').value;
        fetch(base + '/' + id + '/reject', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_notes: notes })
        }).then(r => r.json()).then(function(data) {
            if (data.status) { bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide(); alert('Rejected.'); location.reload(); }
            else { alert(data.message || 'Failed'); }
        }).catch(function() { alert('Request failed'); });
    });
});
</script>
@endsection

