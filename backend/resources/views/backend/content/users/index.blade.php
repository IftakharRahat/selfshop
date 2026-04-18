@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}- Users
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
                <li class="breadcrumb-item active">All Users</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Users List</h6>
            <div class="admin-card-actions">
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create User
                </a>
            </div>
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
                            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                <table class="table admin-table mb-0" id="roleinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Phone</th>
                            <th>Analytics</th>
                            <th>Status</th>
                            <th>Action</th>
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
    var token = $("input[name='_token']").val();
    $(".datepicker").flatpickr();
    var brandinfo = $('#roleinfo').DataTable({
        order: [
            [0, 'desc']
        ],
        processing: true,
        serverSide: true,
        ajax: {
            url: "{!! route('admin.user.data') !!}",
            data: {
                startDate: function() { return $('#startDate').val() },
                endDate: function() { return $('#endDate').val() },
                phone: function() { return $('#phone').val() },
                status_filter: function() { return $('#statusFilter').val() },
                membership_filter: function() { return $('#membershipFilter').val() }
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'user', name: 'name' },
            { data: 'type', orderable: false, searchable: false },
            { data: 'email', name: 'email' },
            { data: 'analytics', orderable: false, searchable: false },
            {
                "data": null, orderable: false, searchable: false,
                render: function(data) {
                    if (data.status === 'Active') {
                        return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="brandstatusBtn" data-id="' + data.id + '">Active</button>';
                    } else {
                        if (data.status === 'Block') {
                            return '<button type="button" class="btn btn-danger btn-sm btn-status" data-status="Active" id="brandstatusBtn" data-id="' + data.id + '">Block</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="brandstatusBtn" data-id="' + data.id + '" >Inactive</button>';
                        }
                    }
                }
            },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $(document).on('change', '#startDate', function(){ brandinfo.ajax.reload(); });
    $(document).on('change', '#endDate', function(){ brandinfo.ajax.reload(); });
    $(document).on('change', '#phone', function(){ brandinfo.ajax.reload(); });

    $('#AddBrand').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: '{{ route('admin.brands.store') }}',
            processData: false, contentType: false,
            data: new FormData(this),
            success: function(data) {
                $('#brand_name').val(''); $('#brand_icon').val('');
                swal({ title: "Success!", icon: "success" });
                brandinfo.ajax.reload();
            },
            error: function(error) { console.log('error'); }
        });
    });

    $(document).on('click', '#editBrandBtn', function() {
        let brandId = $(this).data('id');
        $.ajax({
            type: 'GET', url: 'brands/' + brandId + '/edit',
            success: function(data) {
                $('#EditBrand').find('#brand_name').val(data.brand_name);
                $('#EditBrand').find('#brand_id').val(data.id);
                $('#previmg').html('');
                var prevSrc = data.brand_icon && data.brand_icon.startsWith('http') ? data.brand_icon : '../' + data.brand_icon;
                $('#previmg').append(`<img src="` + prevSrc + `" alt="" style="height: 80px" />`);
                $('#EditBrand').attr('data-id', data.id);
            },
            error: function(error) { console.log('error'); }
        });
    });

    $('#EditBrand').submit(function(e) {
        e.preventDefault();
        let brandId = $('#brand_id').val();
        $.ajax({
            type: 'POST', url: 'brand/' + brandId,
            processData: false, contentType: false,
            data: new FormData(this),
            success: function(data) {
                $('#EditBrand').find('#brand_name').val('');
                $('#previmg').html('');
                swal({ title: "Brand update successfully !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                brandinfo.ajax.reload();
            },
            error: function(error) { console.log('error'); }
        });
    });

    $(document).on('click', '#deleteBrandBtn', function() {
        let brandId = $(this).data('id');
        swal({ title: "Are you sure?", text: "Once deleted, you will not be able to recover this !", icon: "warning", buttons: true, dangerMode: true })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        type: 'DELETE', url: 'brands/' + brandId, data: { '_token': token },
                        success: function(data) {
                            swal("Brand has been deleted!", { icon: "success" });
                            brandinfo.ajax.reload();
                        },
                        error: function(error) { console.log('error'); }
                    });
                } else { swal("Your data is safe!"); }
            });
    });

    $(document).on('click', '#brandstatusBtn', function() {
        let brandId = $(this).data('id');
        let brandStatus = $(this).data('status');
        $.ajax({
            type: 'PUT', url: 'brand/status',
            data: { brand_id: brandId, status: brandStatus, '_token': token },
            success: function(data) {
                swal({ title: "Status updated !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                brandinfo.ajax.reload();
            },
            error: function(error) { console.log('error'); }
        });
    });
});
</script>

@endsection
