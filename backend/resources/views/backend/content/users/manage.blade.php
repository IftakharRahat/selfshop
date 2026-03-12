@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}- Manage Users
    @endsection
@php
    $statusFilter = request('status', '');
    $membershipFilter = request('membership', '');
@endphp

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Manage Users</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Manage Users</h6>
        </div>
        <div class="admin-card-body">
            {{-- Filters --}}
            <div class="row mb-3 g-2">
                <div class="col-md-2">
                    <label style="font-size: 12px; font-weight: 500; margin-bottom: 4px; display: block;">Start Date</label>
                    <input type="text" class="form-control form-control-sm datepicker" id="startDate" value="<?php echo date('Y-m-d')?>" placeholder="Select Date">
                </div>
                <div class="col-md-2">
                    <label style="font-size: 12px; font-weight: 500; margin-bottom: 4px; display: block;">End Date</label>
                    <input type="text" class="form-control form-control-sm datepicker" id="endDate" value="<?php echo date('Y-m-d')?>" placeholder="Select Date">
                </div>
                <div class="col-md-3">
                    <label style="font-size: 12px; font-weight: 500; margin-bottom: 4px; display: block;">Enter Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control form-control-sm" placeholder="Search by phone...">
                </div>
                <div class="col-md-3">
                    <label style="font-size: 12px; font-weight: 500; margin-bottom: 4px; display: block;">Active Filter</label>
                    <div class="d-flex align-items-center gap-2">
                        @if($statusFilter !== '' || $membershipFilter !== '')
                            <span class="badge bg-info">
                                {{ $statusFilter !== '' ? 'Status: '.$statusFilter : '' }}
                                {{ $membershipFilter !== '' ? ' Membership: '.$membershipFilter : '' }}
                            </span>
                            <a href="{{ url('admin/manage-users') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                        @else
                            <span class="text-muted small">No quick filter</span>
                        @endif
                    </div>
                </div>
                <input type="hidden" id="statusFilter" value="{{ $statusFilter }}">
                <input type="hidden" id="membershipFilter" value="{{ $membershipFilter }}">
            </div>
        </div>
        <div class="admin-card-body p-0" style="border-top: 1px solid var(--admin-border, #f1f5f9);">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="manageUsersTable" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Phone</th>
                            <th>Analytics</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $(".datepicker").flatpickr();
    var manageTable = $('#manageUsersTable').DataTable({
        order: [
            [0, 'desc']
        ],
        processing: true,
        serverSide: true,
        ajax: {
            url: "{!! route('admin.manage-user.data') !!}",
            data: {
                startDate: function() { return $('#startDate').val() },
                endDate: function() { return $('#endDate').val() },
                phone: function() { return $('#phone').val() },
                status_filter: function() { return $('#statusFilter').val() },
                membership_filter: function() { return $('#membershipFilter').val() }
            }
        },
        columns: [
            { data: 'id' },
            { data: 'user' },
            { data: 'type', orderable: false, searchable: false },
            { data: 'email' },
            { data: 'analytics' },
            {
                "data": null,
                render: function(data) {
                    if (data.status === 'Active') {
                        return '<span class="badge bg-success">Active</span>';
                    } else {
                        if (data.status === 'Block') {
                            return '<span class="badge bg-danger">Block</span>';
                        } else {
                            return '<span class="badge bg-warning text-dark">Inactive</span>';
                        }
                    }
                }
            },
        ]
    });

    $(document).on('change', '#startDate', function(){ manageTable.ajax.reload(); });
    $(document).on('change', '#endDate', function(){ manageTable.ajax.reload(); });
    $(document).on('change', '#phone', function(){ manageTable.ajax.reload(); });
});
</script>

@endsection
