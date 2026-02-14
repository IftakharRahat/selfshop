@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }} - Vendor Payout Requests
@endsection

<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <div class="d-flex align-items-center justify-content-between" style="width: 50%;float:left;">
                    <h6 class="mb-0">Vendor Payout Requests</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 rounded bg-secondary h-100">
                <div class="row">
                    <div class="col-lg-3">
                        <a href="{{ url('admin/view-vendor-payout-requests/pending') }}">
                            <div class="card card-body {{ ($currentStatus ?? '') === 'pending' ? 'border-primary' : '' }}">
                                <p>Pending</p>
                                <h4>{{ $counts['pending'] ?? 0 }}</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3">
                        <a href="{{ url('admin/view-vendor-payout-requests/approved') }}">
                            <div class="card card-body {{ ($currentStatus ?? '') === 'approved' ? 'border-success' : '' }}">
                                <p>Approved</p>
                                <h4>{{ $counts['approved'] ?? 0 }}</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3">
                        <a href="{{ url('admin/view-vendor-payout-requests/rejected') }}">
                            <div class="card card-body {{ ($currentStatus ?? '') === 'rejected' ? 'border-danger' : '' }}">
                                <p>Rejected</p>
                                <h4>{{ $counts['rejected'] ?? 0 }}</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3">
                        <a href="{{ url('admin/view-vendor-payout-requests') }}">
                            <div class="card card-body {{ ($currentStatus ?? null) === null ? 'border-secondary' : '' }}">
                                <p>All</p>
                                <h4>{{ ($counts['pending'] ?? 0) + ($counts['approved'] ?? 0) + ($counts['rejected'] ?? 0) }}</h4>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="data-tables mt-4">
                    <table class="table table-dark" width="100%" style="text-align: center;">
                        <thead class="thead-light">
                            <tr>
                                <th>SL</th>
                                <th>Date</th>
                                <th>Vendor</th>
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
                                        <small>{{ $req->vendor->contact_email ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if($req->payoutAccount)
                                            {{ $req->payoutAccount->account_name ?? $req->payoutAccount->channel_type }}<br>
                                            <small>***{{ substr($req->payoutAccount->account_number ?? '', -4) }}</small>
                                        @else
                                            —
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
                                        @if($req->status === 'pending')
                                            <button type="button" class="btn btn-success btn-sm btn-approve" data-id="{{ $req->id }}">Approve</button>
                                            <button type="button" class="btn btn-danger btn-sm btn-reject" data-id="{{ $req->id }}">Reject</button>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4">No payout requests.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                <h5 class="modal-title">Reject payout request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject-id">
                <label class="form-label">Note (optional)</label>
                <textarea id="reject-notes" class="form-control" rows="2" placeholder="Admin notes"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
