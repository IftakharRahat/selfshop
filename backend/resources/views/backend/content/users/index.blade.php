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
                    <h6 class="mb-0">Users List</h6>
                </div>
                <div class="" style="width: 50%;float:left;">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-dark" style="color:red;float: right"> + Create User</a>
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
                phone: function() { return $('#phone').val() }
            }
        },
        columns: [
            {
                data: 'id'
            },
            {
                data: 'user'
            },
            {
                data: 'type',
                orderable: false,
                searchable: false,
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
    //add brand

    $('#AddBrand').submit(function(e) {
        e.preventDefault();

        $.ajax({
            type: 'POST',
            uploadUrl: '{{ route('admin.brands.store') }}',
            processData: false,
            contentType: false,
            data: new FormData(this),

            success: function(data) {
                $('#brand_name').val('');
                $('#brand_icon').val('');

                swal({
                    title: "Success!",
                    icon: "success",
                });
                brandinfo.ajax.reload();
            },
            error: function(error) {
                console.log('error');
            }
        });
    });

    //edit brand
    $(document).on('click', '#editBrandBtn', function() {
        let brandId = $(this).data('id');

        $.ajax({
            type: 'GET',
            url: 'brands/' + brandId + '/edit',

            success: function(data) {
                $('#EditBrand').find('#brand_name').val(data
                    .brand_name);
                $('#EditBrand').find('#brand_id').val(data.id);

                $('#previmg').html('');
                $('#previmg').append(`
                    <img  src="../` + data.brand_icon + `" alt = "" style="height: 80px" />
                `);

                $('#EditBrand').attr('data-id', data.id);
            },
            error: function(error) {
                console.log('error');
            }

        });
    });

    //update brand
    $('#EditBrand').submit(function(e) {
        e.preventDefault();
        let brandId = $('#brand_id').val();

        $.ajax({
            type: 'POST',
            url: 'brand/' + brandId,
            processData: false,
            contentType: false,
            data: new FormData(this),

            success: function(data) {
                $('#EditBrand').find('#brand_name').val('');
                $('#previmg').html('');

                swal({
                    title: "Brand update successfully !",
                    icon: "success",
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                });
                brandinfo.ajax.reload();

            },
            error: function(error) {
                console.log('error');
            }
        });
    });

    // delete brand

    $(document).on('click', '#deleteBrandBtn', function() {
        let brandId = $(this).data('id');
        swal({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover this !",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        type: 'DELETE',
                        url: 'brands/' + brandId,
                        data: {
                            '_token': token
                        },
                        success: function(data) {
                            swal("Brand has been deleted!", {
                                icon: "success",
                            });
                            brandinfo.ajax.reload();
                        },
                        error: function(error) {
                            console.log('error');
                        }

                    });


                } else {
                    swal("Your data is safe!");
                }
            });

    });

    // status update

    $(document).on('click', '#brandstatusBtn', function() {
        let brandId = $(this).data('id');
        let brandStatus = $(this).data('status');

        $.ajax({
            type: 'PUT',
            url: 'brand/status',
            data: {
                brand_id: brandId,
                status: brandStatus,
                '_token': token
            },

            success: function(data) {
                swal({
                    title: "Status updated !",
                    icon: "success",
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                });
                brandinfo.ajax.reload();
            },
            error: function(error) {
                console.log('error');
            }

        });
    });

});
</script>

@endsection
