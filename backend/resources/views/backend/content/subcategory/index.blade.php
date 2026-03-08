@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Subcategory
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Subcategory List</h6>
                    <div class="admin-card-actions">
                        <a type="button" data-bs-toggle="modal" data-bs-target="#mainSubcategory" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Create Subcategory
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table" id="subcategoryinfo" width="100%">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Category Name</th>
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

        {{-- create subcategory modal --}}
        <div class="modal fade" id="mainSubcategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Create New Subcategory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="AddSubcategory" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Subcategory Name</label>
                                <input type="text" class="form-control" name="sub_category_name"
                                    id="sub_category_name" placeholder="Enter subcategory name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" id="category_id" class="form-select"
                                    aria-label="Choose Category">
                                    <option value="">Choose Category</option>
                                    @forelse ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subcategory Icon</label>
                                <input class="form-control" name="subcategory_icon"
                                    id="subcategory_icon" type="file">
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

        {{-- edit subcategory modal --}}
        <div class="modal fade" id="editmainSubcategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Edit Subcategory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="EditSubcategory" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Subcategory Name</label>
                                <input type="text" class="form-control" name="sub_category_name"
                                    id="sub_category_name" placeholder="Enter subcategory name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" id="category_id" class="form-select"
                                    aria-label="Choose Category">
                                    <option value="">Choose Category</option>
                                    @forelse ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subcategory Icon</label>
                                <input class="form-control" name="subcategory_icon"
                                    id="subcategory_icon" type="file">
                            </div>
                            <div class="mb-3 d-flex align-items-center gap-3">
                                <span class="form-label mb-0">Current Icon:</span>
                                <div id="previmg"></div>
                            </div>
                            <input type="text" name="subcategory_id" id="subcategory_id" hidden>
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

        var subcategoryinfo = $('#subcategoryinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.subcategory.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'subcategory_icon',
                    name: 'subcategory_icon',
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
                    data: 'sub_category_name',
                },
                {
                    data: 'categories.category_name'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="subcategorystatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="subcategorystatusBtn" data-id="' +
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


        //add subcategory

        $('#AddSubcategory').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.subcategorys.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#sub_category_name').val('');
                    $('#category_id').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    subcategoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit subcategory
        $(document).on('click', '#editSubcategoryBtn', function() {
            let subcategoryId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'subcategorys/' + subcategoryId + '/edit',

                success: function(data) {
                    $('#EditSubcategory').find('#sub_category_name').val(data
                        .sub_category_name);
                    $('#EditSubcategory').find('#category_id').val(data.category_id);
                    $('#EditSubcategory').find('#subcategory_id').val(data.id);
                    $('#previmg').html('');
                    var prevSrc = data.subcategory_icon && data.subcategory_icon.startsWith('http') ? data.subcategory_icon : '../' + data.subcategory_icon;
                    $('#previmg').append(`
                        <img  src="` + prevSrc + `" alt = "" style="height: 80px" />
                    `);
                    $('#EditSubcategory').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update subcategory
        $('#EditSubcategory').submit(function(e) {
            e.preventDefault();
            let subcategoryId = $('#subcategory_id').val();

            $.ajax({
                type: 'POST',
                url: 'subcategory/' + subcategoryId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditSubcategory').find('#sub_category_name').val('');
                    $('#EditSubcategory').find('#category_id').val('');

                    swal({
                        title: "Subcategory update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    subcategoryinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete subcategory

        $(document).on('click', '#deleteSubcategoryBtn', function() {
            let subcategoryId = $(this).data('id');
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
                            url: 'subcategorys/' + subcategoryId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Subcategory has been deleted!", {
                                    icon: "success",
                                });
                                subcategoryinfo.ajax.reload();
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

        $(document).on('click', '#subcategorystatusBtn', function() {
            let subcategoryId = $(this).data('id');
            let subcategoryStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'subcategory/status',
                data: {
                    subcategory_id: subcategoryId,
                    status: subcategoryStatus,
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
                    subcategoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

    });
</script>

@endsection
