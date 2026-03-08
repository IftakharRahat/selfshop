@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Minicategory
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Minicategory List</h6>
                    <div class="admin-card-actions">
                        <a type="button" data-bs-toggle="modal" data-bs-target="#mainMinicategory" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Create Minicategory
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table" id="minicategoryinfo" width="100%">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Subcategory</th>
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

        {{-- create minicategory modal --}}
        <div class="modal fade" id="mainMinicategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Create New Minicategory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="AddMinicategory" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Minicategory Name</label>
                                <input type="text" class="form-control" name="mini_category_name"
                                    id="mini_category_name" placeholder="Enter minicategory name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id"
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
                            <div class="mb-3">
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory_id" id="subcategory_id" class="form-select"
                                    aria-label="Choose Sub-Category">
                                    <option value="">Choose Sub-Category</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Minicategory Icon</label>
                                <input class="form-control" name="minicategory_icon"
                                    id="minicategory_icon" type="file">
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

        {{-- edit minicategory modal --}}
        <div class="modal fade" id="editmainMinicategory" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Edit Minicategory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="EditMinicategory" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Minicategory Name</label>
                                <input type="text" class="form-control" name="mini_category_name"
                                    id="mini_category_name" placeholder="Enter minicategory name">
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
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory_id" id="editsubcategory_id" class="form-select"
                                    aria-label="Choose Sub-Category">
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Minicategory Icon</label>
                                <input class="form-control" name="minicategory_icon"
                                    id="minicategory_icon" type="file">
                            </div>
                            <div class="mb-3 d-flex align-items-center gap-3">
                                <span class="form-label mb-0">Current Icon:</span>
                                <div id="previmg"></div>
                            </div>
                            <input type="text" name="minicategory_id" id="minicategory_id" hidden>
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
                url: '{{ route('admin.minicategorys.store') }}',
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
                    var prevSrc = data.minicategory_icon && data.minicategory_icon.startsWith('http') ? data.minicategory_icon : '../' + data.minicategory_icon;
                    $('#previmg').append(`
                        <img  src="` + prevSrc + `" alt = "" style="height: 80px" />
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
