@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Announcements
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Announcements</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Announcements</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Announcement
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="announcementTable" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create Announcement Modal --}}
    <div class="modal fade" id="createAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddAnnouncement" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="create_title" placeholder="Announcement title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="create_description" rows="4" class="form-control" placeholder="Announcement details..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input class="form-control" name="image" type="file" accept="image/*">
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Announcement Modal --}}
    <div class="modal fade" id="editAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditAnnouncement" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="edit_title" placeholder="Announcement title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" rows="4" class="form-control" placeholder="Announcement details..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image <span class="text-muted">(leave empty to keep current)</span></label>
                            <input class="form-control" name="image" type="file" accept="image/*">
                            <div id="edit_image_preview" class="mt-2"></div>
                        </div>
                        <input type="hidden" name="announcement_id" id="edit_announcement_id">
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Update</button>
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

        var announcementTable = $('#announcementTable').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: '{!! route("announcement.data") !!}',
            columns: [
                { data: 'id' },
                { data: 'image_preview', name: 'image_preview', orderable: false, searchable: false },
                { data: 'title' },
                { data: 'description', render: function(data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return data.length > 80 ? data.substring(0, 80) + '...' : data;
                }},
                { "data": null, render: function(data) {
                    if (data.status === 'Active') {
                        return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="announcementStatusBtn" data-id="' + data.id + '">Active</button>';
                    } else {
                        return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="announcementStatusBtn" data-id="' + data.id + '">Inactive</button>';
                    }
                }},
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Create
        $('#AddAnnouncement').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ route("announcements.store") }}',
                processData: false, contentType: false,
                data: new FormData(this),
                success: function(data) {
                    $('#create_title').val('');
                    $('#create_description').val('');
                    $('#createAnnouncementModal').modal('hide');
                    swal({ title: "Announcement created!", icon: "success" });
                    announcementTable.ajax.reload();
                },
                error: function(error) { console.log('error', error); }
            });
        });

        // Edit - load data
        $(document).on('click', '#editAnnouncementBtn', function() {
            let id = $(this).data('id');
            $.ajax({
                type: 'GET', url: 'announcements/' + id + '/edit',
                success: function(data) {
                    $('#edit_title').val(data.title);
                    $('#edit_description').val(data.description);
                    $('#edit_announcement_id').val(data.id);
                    if (data.image) {
                        var imgSrc = data.image.startsWith('http') ? data.image : '/' + data.image;
                        $('#edit_image_preview').html('<img src="' + imgSrc + '" style="max-height:60px;border-radius:6px;" />');
                    } else {
                        $('#edit_image_preview').html('');
                    }
                },
                error: function(error) { console.log('error', error); }
            });
        });

        // Update
        $('#EditAnnouncement').submit(function(e) {
            e.preventDefault();
            let id = $('#edit_announcement_id').val();
            $.ajax({
                type: 'POST', url: 'announcement/' + id,
                processData: false, contentType: false,
                data: new FormData(this),
                success: function(data) {
                    $('#editAnnouncementModal').modal('hide');
                    swal({ title: "Announcement updated!", icon: "success" });
                    announcementTable.ajax.reload();
                },
                error: function(error) { console.log('error', error); }
            });
        });

        // Status toggle
        $(document).on('click', '#announcementStatusBtn', function() {
            let id = $(this).data('id');
            let status = $(this).data('status');
            $.ajax({
                type: 'PUT', url: 'announcement/status',
                data: { announcement_id: id, status: status, '_token': token },
                success: function(data) {
                    swal({ title: "Status updated!", icon: "success" });
                    announcementTable.ajax.reload();
                },
                error: function(error) { console.log('error', error); }
            });
        });

        // Delete
        $(document).on('click', '#deleteAnnouncementBtn', function() {
            let id = $(this).data('id');
            swal({
                title: "Are you sure?",
                text: "This announcement will be permanently deleted.",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        type: 'DELETE',
                        url: 'announcements/' + id,
                        data: { '_token': token },
                        success: function(data) {
                            swal({ title: "Deleted!", icon: "success" });
                            announcementTable.ajax.reload();
                        },
                        error: function(error) { console.log('error', error); }
                    });
                }
            });
        });
    });
</script>

@endsection
