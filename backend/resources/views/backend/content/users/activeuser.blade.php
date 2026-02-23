@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}- Active Users
    @endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Active Users</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Active Users List</h6>
        </div>
        <div class="admin-card-body">
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
            </div>
        </div>
        <div class="admin-card-body p-0" style="border-top: 1px solid var(--admin-border, #f1f5f9);">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="roleinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>User</th>
                            <th>Shop Name</th>
                            <th>Email</th>
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
    $(document).ready(function(){
        $(".datepicker").flatpickr();
        var brandinfo = $('#roleinfo').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: {
                url: "{!! route('admin.activeuser.data') !!}",
                data: {
                    startDate: function() { return $('#startDate').val() },
                    endDate: function() { return $('#endDate').val() },
                    phone: function() { return $('#phone').val() }
                }
            },
            columns: [
                { data: 'id' },
                { data: 'user' },
                { data: 'shop_name' },
                { data: 'email' },
                { data: 'analytics' },
                {
                    "data": null,
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
    });
</script>

@endsection
