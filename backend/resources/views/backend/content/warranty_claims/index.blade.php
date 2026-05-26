@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }} - Refund Claims
@endsection

@section('subcss')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css">
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Refund Claims</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Warranty / Refund Claims</h6>
        </div>
        <div class="admin-card-body">
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

            {{-- Status filter tabs --}}
            <div class="d-flex gap-2 flex-wrap mb-3">
                <a href="{{ url('admin/warranty-claims/all') }}"
                   class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                    All
                </a>
                <a href="{{ url('admin/warranty-claims/pending') }}"
                   class="btn btn-sm {{ $status === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                    Pending
                </a>
                <a href="{{ url('admin/warranty-claims/approved') }}"
                   class="btn btn-sm {{ $status === 'approved' ? 'btn-success' : 'btn-outline-success' }}">
                    Approved
                </a>
                <a href="{{ url('admin/warranty-claims/rejected') }}"
                   class="btn btn-sm {{ $status === 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
                    Rejected
                </a>
            </div>

            <div class="table-responsive">
                <table class="table admin-table mb-0" id="claimsTable" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Claim#</th>
                            <th>Invoice</th>
                            <th>Reseller</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>Warranty</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <input type="hidden" id="currentStatus" value="{{ $status }}">
</div>

@section('subscript')
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
@endsection

<script>
    $(document).ready(function() {
        var currentStatus = $('#currentStatus').val();
        var claimsTable = $('#claimsTable').DataTable({
            order: [[0, 'desc']],
            processing: true,
            ajax: {
                url: "{{ url('admin/warranty-claims/data') }}/" + currentStatus,
                dataSrc: 'data'
            },
            columns: [
                { data: 'id' },
                { data: 'claim_number' },
                { data: 'invoice_id' },
                { data: 'reseller_name', render: function(data, type, row) {
                    return data + '<br><small class="text-muted">' + (row.reseller_phone || '') + '</small>';
                }},
                { data: 'product_name', render: function(data, type, row) {
                    return data + '<br><small class="text-muted">' + (row.product_code || '') + '</small>';
                }},
                { data: 'supplier_name' },
                { data: 'warranty_days', render: function(data) {
                    return data + ' days';
                }},
                { data: 'days_left', render: function(data, type, row) {
                    if (row.status !== 'pending') return '—';
                    var cls = data > 10 ? 'text-success' : (data > 5 ? 'text-warning' : 'text-danger');
                    return '<span class="fw-bold ' + cls + '">' + data + ' days</span>';
                }},
                { data: 'status', render: function(data) {
                    var badges = {
                        'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                        'approved': '<span class="badge bg-success">Approved</span>',
                        'rejected': '<span class="badge bg-danger">Rejected</span>'
                    };
                    return badges[data] || data;
                }},
                { data: 'created_at' },
                { data: null, orderable: false, searchable: false, render: function(data) {
                    return '<a href="{{ url("admin/warranty-claims") }}/' + data.id + '/show" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>';
                }}
            ]
        });
    });
</script>

@endsection
