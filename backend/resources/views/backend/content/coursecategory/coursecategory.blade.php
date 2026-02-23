@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Course Category
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Course Categories</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Course Category List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainCoursecategory" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Category
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="coursecategoryinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- create course category modal --}}
    <div class="modal fade" id="mainCoursecategory" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create New Course Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddCoursecategory" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="coursecategory_name" id="coursecategory_name" placeholder="Category Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Youtube Code</label>
                            <input type="text" class="form-control" name="youtube_embade" id="youtube_embade" placeholder="Youtube Code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            <input class="form-control" name="coursecategory_image" id="coursecategory_image" type="file">
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="btn" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" name="btn" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- edit course category modal --}}
    <div class="modal fade" id="editmainCoursecategory" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Course Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditCoursecategory" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="coursecategory_name" id="coursecategory_name" placeholder="Category Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Youtube Code</label>
                            <input type="text" class="form-control" name="youtube_embade" id="youtube_embade" placeholder="Youtube Code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            <input class="form-control" name="coursecategory_image" id="coursecategory_image" type="file">
                        </div>
                        <input type="text" name="coursecategory_id" id="coursecategory_id" hidden>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div id="previmg"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="btn" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" name="btn" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        var coursecategoryinfo = $('#coursecategoryinfo').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.coursecategory.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'coursecategory_image',
                    name: 'coursecategory_image',
                    render: function(data, type, full, meta) {
                        return "<img src=../" + data + " height=\"40\" alt='No Image'/>";
                    }
                },
                { data: 'coursecategory_name' },
                {
                    "data": null,
                    render: function(data) {
                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="coursecategorystatusBtn" data-id="' + data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="coursecategorystatusBtn" data-id="' + data.id + '" >Inactive</button>';
                        }
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ]
        });

        $('#AddCoursecategory').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST', uploadUrl: '{{ route('coursecategories.store') }}',
                processData: false, contentType: false, data: new FormData(this),
                success: function(data) {
                    $('#coursecategory_name').val(''); $('#youtube_embade').val(''); $('#coursecategory_image').val('');
                    swal({ title: "Success!", icon: "success" });
                    coursecategoryinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#editCoursecategoryBtn', function() {
            let coursecategoryId = $(this).data('id');
            $.ajax({
                type: 'GET', url: 'coursecategories/' + coursecategoryId + '/edit',
                success: function(data) {
                    $('#EditCoursecategory').find('#coursecategory_name').val(data.coursecategory_name);
                    $('#EditCoursecategory').find('#youtube_embade').val(data.youtube_embade);
                    $('#EditCoursecategory').find('#coursecategory_id').val(data.id);
                    $('#previmg').html('');
                    $('#previmg').append(`<img src="../` + data.coursecategory_image + `" alt="" style="height: 80px" />`);
                    $('#EditCoursecategory').attr('data-id', data.id);
                },
                error: function(error) { console.log('error'); }
            });
        });

        $('#EditCoursecategory').submit(function(e) {
            e.preventDefault();
            let coursecategoryId = $('#coursecategory_id').val();
            $.ajax({
                type: 'POST', url: 'coursecategory/' + coursecategoryId,
                processData: false, contentType: false, data: new FormData(this),
                success: function(data) {
                    $('#EditCoursecategory').find('#coursecategory_name').val('');
                    $('#EditCoursecategory').find('#youtube_embade').val('');
                    $('#previmg').html('');
                    swal({ title: "Coursecategory update successfully !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    coursecategoryinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#deleteCoursecategoryBtn', function() {
            let categoryId = $(this).data('id');
            swal({ title: "Are you sure?", text: "Once deleted, you will not be able to recover this !", icon: "warning", buttons: true, dangerMode: true })
                .then((willDelete) => {
                    if (willDelete) {
                        $.ajax({ type: 'DELETE', url: 'coursecategories/' + categoryId, data: { '_token': token },
                            success: function(data) { swal("Coursecategory has been deleted!", { icon: "success" }); categoryinfo.ajax.reload(); },
                            error: function(error) { console.log('error'); }
                        });
                    } else { swal("Your data is safe!"); }
                });
        });

        $(document).on('click', '#coursecategorystatusBtn', function() {
            let categoryId = $(this).data('id');
            let categoryStatus = $(this).data('status');
            $.ajax({
                type: 'PUT', url: 'coursecategory/status',
                data: { category_id: categoryId, status: categoryStatus, '_token': token },
                success: function(data) {
                    swal({ title: "Status updated !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    coursecategoryinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#categoryfrontstatusBtn', function() {
            let categoryId = $(this).data('id');
            let categoryFrontStatus = $(this).data('status');
            $.ajax({
                type: 'PUT', url: 'category/status',
                data: { category_id: categoryId, front_status: categoryFrontStatus, '_token': token },
                success: function(data) {
                    swal({ title: "Status updated !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    coursecategoryinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });
    });
</script>

@endsection
