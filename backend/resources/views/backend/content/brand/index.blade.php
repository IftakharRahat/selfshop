@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Brands
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Brand List</h6>
                    <div class="admin-card-actions">
                        <a type="button" data-bs-toggle="modal" data-bs-target="#mainBrand" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Create Brand
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table" id="brandinfo" width="100%">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Icon</th>
                                    <th>Name</th>
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

        {{-- create brand modal --}}
        <div class="modal fade" id="mainBrand" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Create New Brand</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="AddBrand" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Brand Name</label>
                                <input type="text" class="form-control" name="brand_name" id="brand_name"
                                    placeholder="Enter brand name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Brand Icon</label>
                                <input class="form-control" name="brand_icon" id="brand_icon"
                                    type="file">
                            </div>
                            <div class="admin-modal-footer">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-outline-secondary btn-sm">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary AddCourierBtn btn-sm">Save</button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End create modal-->

        {{-- edit brand modal --}}
        <div class="modal fade" id="editmainBrand" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Edit Brand</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="EditBrand" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Brand Name</label>
                                <input type="text" class="form-control" name="brand_name" id="brand_name"
                                    placeholder="Enter brand name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Brand Icon</label>
                                <input class="form-control" name="brand_icon" id="brand_icon"
                                    type="file">
                            </div>
                            <input type="text" name="brand_id" id="brand_id" hidden>
                            <div class="mb-3 d-flex align-items-center gap-3">
                                <span class="form-label mb-0">Current Icon:</span>
                                <div id="previmg"></div>
                            </div>
                            <div class="admin-modal-footer">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-outline-secondary btn-sm">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary AddCourierBtn btn-sm">Update</button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End edit modal-->
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        var brandinfo = $('#brandinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.brand.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'brand_icon',
                    name: 'brand_icon',
                    orderable: false,
                    render: function(data, type, full, meta) {
                        if (data && data.trim() !== '') {
                            var imgSrc = data.startsWith('http') ? data : '../' + data;
                            return '<img src="' + imgSrc + '" height="40" style="border-radius:6px;object-fit:cover;width:40px;height:40px;" onerror="this.outerHTML=\'<div style=padding:8px;width:40px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px><i class=bi.bi-image></i></div>\'" />';
                        } else {
                            return '<div style="width:40px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px"><i class="bi bi-image"></i></div>';
                        }
                    }
                },
                {
                    data: 'brand_name'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="brandstatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="brandstatusBtn" data-id="' +
                                data.id + '" >Inactive</button>';
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


        //add brand

        $('#AddBrand').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.brands.store') }}',
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
                    var prevSrc = data.brand_icon && data.brand_icon.startsWith('http') ? data.brand_icon : '../' + data.brand_icon;
                    $('#previmg').append(`
                        <img  src="` + prevSrc + `" alt = "" style="height: 80px" />
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
