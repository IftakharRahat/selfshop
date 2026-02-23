@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Category
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Category List</h6>
                    <div class="admin-card-actions">
                        <a type="button" data-bs-toggle="modal" data-bs-target="#mainCategory" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Create Category
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table" id="categoryinfo" width="100%">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Front View</th>
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

        {{-- create category modal --}}
        <div class="modal fade" id="mainCategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Create New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="AddCategory" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" name="category_name" id="category_name"
                                    placeholder="Enter category name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category Icon</label>
                                <input class="form-control" name="category_icon"
                                    id="category_icon" type="file">
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

        {{-- edit category modal --}}
        <div class="modal fade" id="editmainCategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="EditCategory" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" name="category_name" id="category_name"
                                    placeholder="Enter category name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category Icon</label>
                                <input class="form-control" name="category_icon"
                                    id="category_icon" type="file">
                            </div>
                            <input type="text" name="category_id" id="category_id" hidden>

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

        var categoryinfo = $('#categoryinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.category.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'category_icon',
                    name: 'category_icon',
                    orderable: false,
                    render: function(data, type, full, meta) {
                        if (data && data.trim() !== '') {
                            return '<img src=../' + data + ' height="40" style="border-radius:6px;object-fit:cover;width:40px;height:40px;" onerror="this.outerHTML=\'<div style=padding:8px;width:40px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px><i class=bi.bi-image></i></div>\'" />';
                        } else {
                            return '<div style="width:40px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px"><i class="bi bi-image"></i></div>';
                        }
                    }
                },
                {
                    data: 'category_name'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.front_status == '0') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="1" id="categoryfrontstatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="0" id="categoryfrontstatusBtn" data-id="' +
                                data.id + '" >Inactive</button>';
                        }


                    }
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="categorystatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="categorystatusBtn" data-id="' +
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


        //add category

        $('#AddCategory').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                uploadUrl: '{{ route('admin.categorys.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#category_name').val('');
                    $('#category_icon').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    categoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit category
        $(document).on('click', '#editCategoryBtn', function() {
            let categoryId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'categorys/' + categoryId + '/edit',

                success: function(data) {
                    $('#EditCategory').find('#category_name').val(data
                        .category_name);
                    $('#EditCategory').find('#category_id').val(data.id);

                    $('#previmg').html('');
                    $('#previmg').append(`
                        <img  src="../` + data.category_icon + `" alt = "" style="height: 80px" />
                    `);

                    $('#EditCategory').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update category
        $('#EditCategory').submit(function(e) {
            e.preventDefault();
            let categoryId = $('#category_id').val();

            $.ajax({
                type: 'POST',
                url: 'category/' + categoryId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditCategory').find('#category_name').val('');
                    $('#previmg').html('');

                    swal({
                        title: "Category update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    categoryinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete category

        $(document).on('click', '#deleteCategoryBtn', function() {
            let categoryId = $(this).data('id');
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
                            url: 'categorys/' + categoryId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Category has been deleted!", {
                                    icon: "success",
                                });
                                categoryinfo.ajax.reload();
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

        $(document).on('click', '#categorystatusBtn', function() {
            let categoryId = $(this).data('id');
            let categoryStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'category/status',
                data: {
                    category_id: categoryId,
                    status: categoryStatus,
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
                    categoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        // front status update

        $(document).on('click', '#categoryfrontstatusBtn', function() {
            let categoryId = $(this).data('id');
            let categoryFrontStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'category/status',
                data: {
                    category_id: categoryId,
                    front_status: categoryFrontStatus,
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
                    categoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

    });
</script>

@endsection
