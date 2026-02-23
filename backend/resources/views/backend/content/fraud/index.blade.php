@extends('backend.master')

@section('maincontent')

@section('subcss')
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/jquery-datatables-checkboxes@1.2.13/css/dataTables.checkboxes.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Fraud Listed Requests</li>
            </ol>
        </nav>
    </div>

    {{-- edit fraud modal --}}
    <div class="modal fade" id="editmainFrd" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Fraud Listed Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditMenu" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <input type="text" name="message" id="message" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="Pending">Pending</option>
                                <option value="Accepted">Accepted</option>
                                <option value="Cancel">Cancel</option>
                            </select>
                        </div>
                        <input type="text" name="prq_id" id="prq_id" hidden>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="btn" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" name="btn" class="btn AddCourierBtn" style="background: var(--admin-primary, #2d2a5d); color: #fff;">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Fraud Listed Requests</h6>
        </div>
        <div class="admin-card-body">
            @if (\Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ \Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="d-flex gap-2 flex-wrap mb-3">
                <a href="{{ url('admin/fraud/allfraud') }}" class="btn btn-sm btn-outline-primary">All Fraud</a>
                <a href="{{ url('admin/fraud/Pending') }}" class="btn btn-sm btn-outline-warning">Pending</a>
                <a href="{{ url('admin/fraud/Accepted') }}" class="btn btn-sm btn-outline-success">Accepted</a>
                <a href="{{ url('admin/fraud/Cancel') }}" class="btn btn-sm btn-outline-danger">Cancel</a>
            </div>

            <div class="table-responsive">
                <table class="table admin-table mb-0" id="productrqinfo" width="100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>ADD BY</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($status)
        <input type="text" class="form-control" name="productrq_status" id="productrq_status" value="{{ $status }}" hidden>
    @else
        <input type="text" class="form-control" name="productrq_status" id="productrq_status" value="all" hidden>
    @endif
</div>

@section('subscript')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-datatables-checkboxes@1.2.13/js/dataTables.checkboxes.min.js"></script>
@endsection
<script>
    $(document).ready(function() {
        var statusproductrq = $('#productrq_status').val();
        var productrqinfotbl = $('#productrqinfo').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: { url: "{{ url('admin/fraud/data/') }}" + '/' + statusproductrq },
            columnDefs: [{ targets: 0, checkboxes: { selectRow: false } }],
            columns: [
                { data: 'id' },
                { data: 'user' },
                { data: 'phone' },
                { data: 'message' },
                { "data": null, render: function(data) {
                    if (data.status === 'Pending') return '<button type="button" class="btn btn-primary btn-sm">Pending</button>';
                    else if (data.status === 'Accepted') return '<button type="button" class="btn btn-info btn-sm">Accepted</button>';
                    else return '<button type="button" class="btn btn-danger btn-sm">Cancel</button>';
                }},
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $(document).on('click', '#editFrdBtn', function() {
            var id = $(this).attr('data-id');
            $.ajax({
                type: 'GET',
                url: "{{ url('admin/fraud') }}/" + id + "/edit",
                success: function(data) {
                    $('#EditMenu').find('#phone').val(data.phone);
                    $('#EditMenu').find('#message').val(data.message);
                    $('#EditMenu').find('#status').val(data.status);
                    $('#EditMenu').find('#prq_id').val(data.id);
                    $('#EditMenu').attr('data-id', data.id);
                },
                error: function(error) { console.log('error'); }
            });
        });

        $('#EditMenu').submit(function(e) {
            e.preventDefault();
            let menuId = $('#prq_id').val();
            $.ajax({
                type: 'POST', url: 'update/' + menuId, processData: false, contentType: false,
                data: new FormData(this),
                success: function(data) {
                    $('#EditMenu').find('#phone').val('');
                    $('#EditMenu').find('#message').val('');
                    $('#EditMenu').find('#status').val('');
                    $('#EditMenu').find('#prq_id').val('');
                    $('#previmg').html('');
                    swal({ title: "Fraud Listed request update successfully !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    productrqinfotbl.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#deleteComplainBtn', function() {
            let productrqsId = $(this).data('id');
            swal({ title: "Are you sure?", text: "Once deleted, you will not be able to recover this !", icon: "warning", buttons: true, dangerMode: true })
                .then((willDelete) => {
                    if (willDelete) {
                        $.ajax({ type: 'DELETE', url: 'productrqs/' + productrqsId,
                            success: function(data) { swal("Poof! Your productrq has been deleted!", { icon: "success" }); productrqinfotbl.ajax.reload(); },
                            error: function(error) { console.log('error'); }
                        });
                    } else { swal("Your data is safe!"); }
                });
        });
    });
</script>

@endsection
