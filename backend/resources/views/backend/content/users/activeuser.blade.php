@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}- Users
    @endsection
<style>
    div#roleinfo_length {
        color: red;
    }
    div#roleinfo_filter {
        color: red;
    }
    div#roleinfo_info {
        color: red;
    }
      th {
    text-align: left;
}


td {
    text-align: left;
}
</style>

<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <div class="d-flex align-items-center justify-content-between"  style="width: 50%;float:left;">
                    <h6 class="mb-0">Active Users List</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="mb-2 row ps-4">

                <div class="p-2 form-group col-md-2">
                    <label for="inputCity" class="col-form-label">Start Date</label>
                    <input type="text" class="form-control datepicker" id="startDate"  value="<?php echo date('Y-m-d')?>" placeholder="Select Date">
                </div>
                <div class="p-2 form-group col-md-2">
                    <label for="inputCity" class="col-form-label">End Date</label>
                    <input type="text" class="form-control datepicker" id="endDate" value="<?php echo date('Y-m-d')?>" placeholder="Select Date">
                </div>
                <div class="p-2 form-group col-md-3">
                    <label for="inputState" class="col-form-label">Enter Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control">
                </div>

            </div>
            <div class="p-4 rounded bg-secondary h-100">

                <div class="data-tables">
                    <table class="table table-dark" id="roleinfo" width="100%"  style="text-align: center;">
                        <thead class="thead-light">
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
</div>

<script>

    $(document).ready(function(){

        $(".datepicker").flatpickr();

        var brandinfo = $('#roleinfo').DataTable({
            order: [
                [0, 'desc']
            ],
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
            columns: [{
                    data: 'id'
                },
                {
                    data: 'user'
                },
                {
                    data: 'shop_name'
                },
                {
                    data: 'email'
                },
                {
                    data: 'analytics'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="brandstatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            if (data.status === 'Block') {
                                return '<button type="button" class="btn btn-danger btn-sm btn-status" data-status="Active" id="brandstatusBtn" data-id="' +
                                    data.id + '">Block</button>';
                            } else {
                                return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="brandstatusBtn" data-id="' +
                                    data.id + '" >Inactive</button>';
                            }
                        }


                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },

            ]
        });


        $(document).on('change', '#startDate', function(){
            brandinfo.ajax.reload();
        });
        $(document).on('change', '#endDate', function(){
            brandinfo.ajax.reload();
        });
        $(document).on('change', '#phone', function(){
            brandinfo.ajax.reload();
        });


    });
</script>


@endsection
