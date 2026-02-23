@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Payment Icons
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Payment Icons</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Payment Icon List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainPaymenticon" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Payment Icon
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="paymenticoninfo" width="100%">
                    <thead>
                        <tr>
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

    {{-- create payment icon modal --}}
    <div class="modal fade" id="mainPaymenticon" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create New Payment Icon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddPaymenticon" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Payment Type Name</label>
                            <input type="text" class="form-control" name="payment_type_name" id="payment_type_name" placeholder="Payment Type Name">
                        </div>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Icon Image</label>
                            <input class="form-control" name="payment_icon" id="payment_icon" type="file">
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

    {{-- edit payment icon modal --}}
    <div class="modal fade" id="editmainPaymenticon" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Payment Icon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditPaymenticon" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Payment Type Name</label>
                            <input type="text" class="form-control" name="payment_type_name" id="payment_type_name" placeholder="Payment Type Name">
                        </div>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Icon Image</label>
                            <input class="form-control" name="payment_icon" id="payment_icon" type="file">
                        </div>
                        <input type="text" name="paymenticon_id" id="paymenticon_id" hidden>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Current Icon</label>
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

        var paymenticoninfo = $('#paymenticoninfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.paymenticon.data') !!}',
            columns: [{
                    data: 'payment_icon',
                    name: 'payment_icon',
                    render: function(data, type, full, meta) {
                        return "<img src=../" + data + " height=\"40\" alt='No Image'/>";
                    }
                },
                {
                    data: 'payment_type_name'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="paymenticonstatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="paymenticonstatusBtn" data-id="' +
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


        //add paymenticon

        $('#AddPaymenticon').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                uploadUrl: '{{ route('admin.paymenticons.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#payment_type_name').val('');
                    $('#payment_icon').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    paymenticoninfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit paymenticon
        $(document).on('click', '#editPaymenticonBtn', function() {
            let paymenticonId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'paymenticons/' + paymenticonId + '/edit',

                success: function(data) {
                    $('#EditPaymenticon').find('#payment_type_name').val(data
                        .payment_type_name);
                    $('#EditPaymenticon').find('#paymenticon_id').val(data.id);

                    $('#previmg').html('');
                    $('#previmg').append(`
                        <img  src="../` + data.payment_icon + `" alt = "" style="height: 40px" />
                    `);

                    $('#EditPaymenticon').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update paymenticon
        $('#EditPaymenticon').submit(function(e) {
            e.preventDefault();
            let paymenticonId = $('#paymenticon_id').val();

            $.ajax({
                type: 'POST',
                url: 'paymenticon/' + paymenticonId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditPaymenticon').find('#payment_type_name').val('');
                    $('#previmg').html('');

                    swal({
                        title: "Paymenticon update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    paymenticoninfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete paymenticon

        $(document).on('click', '#deletePaymenticonBtn', function() {
            let paymenticonId = $(this).data('id');
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
                            url: 'paymenticons/' + paymenticonId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Paymenticon has been deleted!", {
                                    icon: "success",
                                });
                                paymenticoninfo.ajax.reload();
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

        $(document).on('click', '#paymenticonstatusBtn', function() {
            let paymenticonId = $(this).data('id');
            let paymenticonStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'paymenticon/status',
                data: {
                    paymenticon_id: paymenticonId,
                    status: paymenticonStatus,
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
                    paymenticoninfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

    });
</script>

@endsection
