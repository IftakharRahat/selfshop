@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Minicategory
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
</style>

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="h-100 bg-secondary rounded p-4 pb-0">
                <div class="d-flex align-items-center justify-content-between" style="width: 50%;float:left;">
                    <h6 class="mb-0">Minicategory List</h6>
                </div>
                <div class="" style="width: 50%;float:left;">
                    <a type="button" data-bs-toggle="modal" data-bs-target="#mainMinicategory"
                        class="btn btn-primary m-2" style="float: right"> + Create Minicategory</a>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="bg-secondary rounded h-100 p-4">
                <div class="data-tables">
                    <table class="table table-dark" id="minicategoryinfo" width="100%" style="text-align: center;">
                        <thead class="thead-light">
                            <tr>
                                <th>SL</th>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- create payment icon --}}
        <div class="modal fade" id="mainMinicategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-secondary rounded h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Create New Minicategory</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="AddMinicategory" enctype="multipart/form-data">
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="mini_category_name"
                                    id="mini_category_name" placeholder="Minicategory Name">
                                <label for="floatingInput">Minicategory Name</label>
                            </div>

                            <div class="form-group mb-3">
                                <label for="ProductCategory" style="width: 100%;">Categories <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" style="background: black;"
                                    name="category_id" onchange="setsubcategory()" required>
                                    <option>Select Category</option>
                                    @forelse ($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->category_name }}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="mt-4 mb-4">
                                <select name="subcategory_id" id="subcategory_id" class="form-select form-select-lg mb-3"
                                    aria-label=".form-select-lg example">
                                    <option value="">Choose Sub-Category</option>
                                </select>
                            </div>

                            <div class="mt-4 mb-4">
                                <input class="form-control form-control-lg bg-dark" name="minicategory_icon"
                                    id="minicategory_icon" type="file">
                            </div>
                            <br>
                            <div class="form-group mt-2" style="text-align: right">
                                <div class="minimitBtnSCourse">
                                    <button type="minimit" name="btn" data-bs-dismiss="modal"
                                        class="btn btn-dark btn-block" style="float: left">Close</button>
                                    <button type="minimit" name="btn"
                                        class="btn btn-primary AddCourierBtn btn-block">Save</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->

        {{-- edit payment icon --}}
        <div class="modal fade" id="editmainMinicategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-secondary rounded h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Edit Minicategory</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="EditMinicategory" enctype="multipart/form-data">
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="mini_category_name"
                                    id="mini_category_name" placeholder="Minicategory Name">
                                <label for="floatingInput">Minicategory Name</label>
                            </div>

                            <div class="mt-4 mb-4">
                                <select name="category_id" id="category_id" class="form-select form-select-lg mb-3"
                                    aria-label=".form-select-lg example">
                                    <option value="">Choose Category</option>
                                    @forelse ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>

                            <div class="mt-4 mb-4">
                                <select name="subcategory_id" id="editsubcategory_id" class="form-select form-select-lg mb-3"
                                    aria-label=".form-select-lg example">
                                </select>
                            </div>

                            <div class="mt-4 mb-4">
                                <input class="form-control form-control-lg bg-dark" name="minicategory_icon"
                                    id="minicategory_icon" type="file">
                            </div>
                            <div class="m-3 ms-0 mb-0"
                                style="text-align: center;height: 100px;margin-top:20px !important">
                                <h4 style="width:30%;float: left;text-align: left;">Icon : </h4>
                                <div id="previmg" style="float: left;"></div>
                            </div>
                            <input type="text" name="minicategory_id" id="minicategory_id" hidden>
                            <br>
                            <div class="form-group mt-2" style="text-align: right">
                                <div class="minimitBtnSCourse">
                                    <button type="minimit" name="btn" data-bs-dismiss="modal"
                                        class="btn btn-dark btn-block" style="float: left">Close</button>
                                    <button type="minimit" name="btn"
                                        class="btn btn-primary AddCourierBtn btn-block">Update</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        var minicategoryinfo = $('#minicategoryinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.minicategory.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'minicategory_icon',
                    name: 'minicategory_icon',
                    render: function(data, type, full, meta) {
                        return "<img src=../" + data + " height=\"40\" alt='No Image'/>";
                    }
                },
                {
                    data: 'mini_category_name',
                },
                {
                    data: 'categories.category_name'
                },
                {
                    data: 'subcategories.sub_category_name'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="minicategorystatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="minicategorystatusBtn" data-id="' +
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


        //add minicategory

        $('#AddMinicategory').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                uploadUrl: '{{ route('admin.minicategorys.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#mini_category_name').val('');
                    $('#category_id').val('');
                    $('#subcategory_id').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    minicategoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit minicategory
        $(document).on('click', '#editMinicategoryBtn', function() {
            let minicategoryId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'minicategorys/' + minicategoryId + '/edit',

                success: function(data) {
                    $('#EditMinicategory').find('#mini_category_name').val(data.mini_category_name);
                    $('#EditMinicategory').find('#category_id').val(data.category_id);
                    $('#EditMinicategory').find('#minicategory_id').val(data.id);

                    $.ajax({
                        type: 'GET',
                        url: 'get/subcategory/' + data.category_id,

                        success: function(data) {
                            $('#editsubcategory_id').html('');

                            for (var i = 0; i < data.length; i++) {
                                $('#editsubcategory_id').append(`
                                    <option value="` + data[i].id + `" >` + data[i].sub_category_name + `</option>
                                `)
                            }
                        },
                        error: function(error) {
                            console.log('error');
                        }
                    });
                    $('#previmg').html('');
                    $('#previmg').append(`
                        <img  src="../` + data.minicategory_icon + `" alt = "" style="height: 80px" />
                    `);
                    $('#EditMinicategory').attr('data-id', data.id);
                    $('#EditMinicategory').find('#editsubcategory_id').val(data.subcategory_id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update minicategory
        $('#EditMinicategory').submit(function(e) {
            e.preventDefault();
            let minicategoryId = $('#minicategory_id').val();

            $.ajax({
                type: 'POST',
                url: 'minicategory/' + minicategoryId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditMinicategory').find('#mini_category_name').val('');
                    $('#EditMinicategory').find('#category_id').val('');

                    swal({
                        title: "Minicategory update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    minicategoryinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete minicategory

        $(document).on('click', '#deleteMinicategoryBtn', function() {
            let minicategoryId = $(this).data('id');
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
                            url: 'minicategorys/' + minicategoryId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Minicategory has been deleted!", {
                                    icon: "success",
                                });
                                minicategoryinfo.ajax.reload();
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

        $(document).on('click', '#minicategorystatusBtn', function() {
            let minicategoryId = $(this).data('id');
            let minicategoryStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'minicategory/status',
                data: {
                    minicategory_id: minicategoryId,
                    status: minicategoryStatus,
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
                    minicategoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });



    });

    function setsubcategory() {
        var sub_id = $('#category_id').val();
        $.ajax({
            type: 'GET',
            url: 'get/subcategory/' + sub_id,

            success: function(data) {
                $('#subcategory_id').html('');

                for (var i = 0; i < data.length; i++) {
                    $('#subcategory_id').append(`
                            <option value="` + data[i].id + `" >` + data[i].sub_category_name + `</option>
                        `)
                }
            },
            error: function(error) {
                console.log('error');
            }
        });
    }

</script>

@endsection
