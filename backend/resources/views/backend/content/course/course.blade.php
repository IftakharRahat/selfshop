@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Course
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Courses</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Course List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainCourse" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Course
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="courseinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Thumbnail</th>
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

    {{-- create course modal --}}
    <div class="modal fade" id="mainCourse" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddCourse" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Course Name</label>
                            <input type="text" class="form-control" name="course_name" id="course_name" placeholder="Course Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Youtube Code</label>
                            <input type="text" class="form-control" name="youtube_embade" id="youtube_embade" placeholder="Youtube Code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Category</label>
                            <select name="coursecategory_id" class="form-select" id="coursecategory_id">
                               @forelse(App\Models\Coursecategory::where('status','Active')->get() as $category)
                                 <option value="{{$category->id}}">{{$category->coursecategory_name}}</option>
                               @empty
                               @endforelse
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Image</label>
                            <input class="form-control" name="course_image" id="course_image" type="file">
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

    {{-- edit course modal --}}
    <div class="modal fade" id="editmainCourse" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditCourse" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Course Name</label>
                            <input type="text" class="form-control" name="course_name" id="course_name" placeholder="Course Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Youtube Code</label>
                            <input type="text" class="form-control" name="youtube_embade" id="youtube_embade" placeholder="Youtube Code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Category</label>
                            <select name="coursecategory_id" class="form-select" id="coursecategory_id">
                               @forelse(App\Models\Coursecategory::where('status','Active')->get() as $category)
                                 <option value="{{$category->id}}">{{$category->coursecategory_name}}</option>
                               @empty
                               @endforelse
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Image</label>
                            <input class="form-control" name="course_image" id="course_image" type="file">
                        </div>
                        <input type="text" name="course_id" id="course_id" hidden>
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

        var courseinfo = $('#courseinfo').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: '{!! route('course.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'course_image',
                    name: 'course_image',
                    render: function(data, type, full, meta) {
                        var imgSrc = data && data.startsWith('http') ? data : '../' + data;
                        return "<img src='" + imgSrc + "' height=\"40\" alt='No Image'/>";
                    }
                },
                {
                    data: 'course_name'
                },
                {
                    "data": null,
                    render: function(data) {
                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="coursestatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="coursestatusBtn" data-id="' +
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

        $('#AddCourse').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ route('courses.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),
                success: function(data) {
                    $('#course_name').val('');
                    $('#youtube_embade').val('');
                    $('#course_image').val('');
                    swal({ title: "Success!", icon: "success" });
                    courseinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#editCourseBtn', function() {
            let courseId = $(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'courses/' + courseId + '/edit',
                success: function(data) {
                    $('#EditCourse').find('#course_name').val(data.course_name);
                    $('#EditCourse').find('#youtube_embade').val(data.youtube_embade);
                    $('#EditCourse').find('#coursecategory_id').val(data.coursecategory_id);
                    $('#EditCourse').find('#course_id').val(data.id);
                    $('#previmg').html('');
                    var prevSrc = data.course_image && data.course_image.startsWith('http') ? data.course_image : '../' + data.course_image;
                    $('#previmg').append(`<img src="` + prevSrc + `" alt="" style="height: 80px" />`);
                    $('#EditCourse').attr('data-id', data.id);
                },
                error: function(error) { console.log('error'); }
            });
        });

        $('#EditCourse').submit(function(e) {
            e.preventDefault();
            let courseId = $('#course_id').val();
            $.ajax({
                type: 'POST', url: 'course/' + courseId,
                processData: false, contentType: false,
                data: new FormData(this),
                success: function(data) {
                    $('#EditCourse').find('#course_name').val('');
                    $('#EditCourse').find('#youtube_embade').val('');
                    $('#previmg').html('');
                    swal({ title: "Course update successfully !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    courseinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#deleteCourseBtn', function() {
            let categoryId = $(this).data('id');
            swal({ title: "Are you sure?", text: "Once deleted, you will not be able to recover this !", icon: "warning", buttons: true, dangerMode: true })
                .then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            type: 'DELETE', url: 'courses/' + categoryId, data: { '_token': token },
                            success: function(data) { swal("Course has been deleted!", { icon: "success" }); categoryinfo.ajax.reload(); },
                            error: function(error) { console.log('error'); }
                        });
                    } else { swal("Your data is safe!"); }
                });
        });

        $(document).on('click', '#coursestatusBtn', function() {
            let categoryId = $(this).data('id');
            let categoryStatus = $(this).data('status');
            $.ajax({
                type: 'PUT', url: 'course/status',
                data: { category_id: categoryId, status: categoryStatus, '_token': token },
                success: function(data) {
                    swal({ title: "Status updated !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    courseinfo.ajax.reload();
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
                    courseinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });
    });
</script>

@endsection
