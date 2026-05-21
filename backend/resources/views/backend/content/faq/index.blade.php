@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Faq
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">FAQ</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">FAQ List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainFaq" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create FAQ
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="faqinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Question</th>
                            <th>Answer</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- create faq modal --}}
    <div class="modal fade" id="mainFaq" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create New FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddFaq" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Question</label>
                            <input type="text" class="form-control" name="question" id="question" placeholder="Question">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Youtube Embed Code</label>
                            <input type="text" class="form-control" name="youtube_embade" id="youtube_embade" placeholder="Youtube Embed Code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Answer</label>
                            <textarea name="answer" id="answer" rows="3" class="form-control"></textarea>
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

    {{-- edit faq modal --}}
    <div class="modal fade" id="editmainFaq" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditFaq" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Question</label>
                            <input type="text" class="form-control" name="question" id="question" placeholder="Question">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Youtube Embed Code</label>
                            <input type="text" class="form-control" name="youtube_embade" id="youtube_embade" placeholder="Youtube Embed Code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Answer</label>
                            <textarea name="answer" id="answer" rows="3" class="form-control"></textarea>
                        </div>
                        <input type="text" name="faq_id" id="faq_id" hidden>
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

        var faqinfo = $('#faqinfo').DataTable({
            order: [[0, 'asc']],
            processing: true,
            serverSide: true,
            ajax: '{!! route('faq.data') !!}',
            columns: [
                {
                    data: 'id',
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'question' },
                { data: 'answer' },
                { "data": null, render: function(data) {
                    if (data.status === 'Active') {
                        return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="faqstatusBtn" data-id="' + data.id + '">Active</button>';
                    } else {
                        return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="faqstatusBtn" data-id="' + data.id + '">Inactive</button>';
                    }
                }},
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#AddFaq').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ route('faqs.store') }}',
                processData: false, contentType: false,
                data: new FormData(this),
                success: function(data) {
                    $('#question').val('');
                    $('#answer').val('');
                    $('#youtube_embade').val('');
                    swal({ title: "Success!", icon: "success" });
                    faqinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#editFaqBtn', function() {
            let faqId = $(this).data('id');
            $.ajax({
                type: 'GET', url: 'faqs/' + faqId + '/edit',
                success: function(data) {
                    $('#EditFaq').find('#question').val(data.question);
                    $('#EditFaq').find('#answer').val(data.answer);
                    $('#EditFaq').find('#youtube_embade').val(data.youtube_embade);
                    $('#EditFaq').find('#faq_id').val(data.id);
                    $('#EditFaq').attr('data-id', data.id);
                },
                error: function(error) { console.log('error'); }
            });
        });

        $('#EditFaq').submit(function(e) {
            e.preventDefault();
            let faqId = $('#faq_id').val();
            $.ajax({
                type: 'POST', url: 'faq/' + faqId,
                processData: false, contentType: false,
                data: new FormData(this),
                success: function(data) {
                    $('#EditFaq').find('#question').val('');
                    $('#EditFaq').find('#answer').val('');
                    $('#EditFaq').find('#youtube_embade').val('');
                    swal({ title: "Faq update successfully !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    faqinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#faqstatusBtn', function() {
            let faqId = $(this).data('id');
            let faqStatus = $(this).data('status');
            $.ajax({
                type: 'PUT', url: 'faq/status',
                data: { faq_id: faqId, status: faqStatus, '_token': token },
                success: function(data) {
                    swal({ title: "Status updated !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    faqinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });
    });
</script>

@endsection
