@extends('backend.master')

@section('maincontent')
    <div class="container-fluid pt-4 px-4">

        <div class="pagetitle row">
            <div class="col-6">
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Paymenttypes</li>
                    </ol>
                </nav>
            </div>
            <div class="col-6" style="text-align: right">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#mainPaymenttype"><span style="font-weight: bold;">+</span> Add New Paymenttype</button>
            </div>
        </div><!-- End Page Title -->

        {{-- //popup modal for create user --}}
        <div class="modal fade" id="mainPaymenttype" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Paymenttype</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="AddPaymenttype" enctype="multipart/form-data">
                            @csrf
                            <div class="successSMS"></div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">PaymentType Name</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="paymentTypeName" id="paymentTypeName"
                                        required>
                                    <span
                                        class="text-danger">{{ $errors->has('paymentTypeName') ? $errors->first('paymentTypeName') : '' }}</span>
                                </div>
                            </div>

                            <div class="mt-4 mb-4">
                                <input class="form-control form-control-lg bg-dark" name="icon" id="icon"
                                    type="file">
                            </div>

                            <div class="form-group" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn"
                                        class="btn btn-primary AddPaymenttypeBtn btn-block">Save</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->

        {{-- //table section for category --}}

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body pt-4">
                            @if (\Session::has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-1"></i>
                                    {{ \Session::get('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif
                            <!-- Table with stripped rows -->
                            <div class="table-responsive">
                                <table class="table table-centered table-borderless table-hover mb-0" id="paymenttpinfo"
                                    width="100%">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Icon</th>
                                            <th>Payment Type Name</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <!-- End Table with stripped rows -->

                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- //popup modal for edit user --}}
        <div class="modal fade" id="editmainPaymenttype" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Paymenttype</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="EditPaymenttype" enctype="multipart/form-data">
                            @csrf
                            <div class="successSMS"></div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">PaymentType Name</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="paymentTypeName"
                                        id="editpaymentTypeName" required>
                                    <span
                                        class="text-danger">{{ $errors->has('paymentTypeName') ? $errors->first('paymentTypeName') : '' }}</span>
                                </div>
                            </div>
                            <div class="mt-4 mb-4">
                                <input class="form-control form-control-lg bg-dark" name="icon" id="icon"
                                    type="file">
                            </div>
                            <div class="m-3 ms-0 mb-0"
                                style="text-align: center;height: 100px;margin-top:20px !important">
                                <h4 style="width:30%;float: left;text-align: left;">Icon : </h4>
                                <div id="previmg" style="float: left;"></div>
                            </div>
                            <br>
                            <input type="text" name="id" id="idhidden" hidden>
                            <div class="form-group" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn" class="btn btn-primary btn-block">Update</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->

    </div>

    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

    <script>
        $(document).ready(function() {
            var token = $("input[name='_token']").val();
            var paymenttypeinfotbl = $('#paymenttpinfo').DataTable({
                order: [
                    [0, 'desc']
                ],
                processing: true,
                serverSide: true,
                ajax: '{!! route('paymenttype.info') !!}',
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'icon',
                        name: 'icon',
                        render: function(data, type, full, meta) {
                            return "<img src=" + data + " height=\"40\" alt='No Image'/>";
                        }
                    },
                    {
                        data: 'paymentTypeName'
                    },
                    {
                        "data": null,
                        render: function(data) {

                            if (data.status === 'Active') {
                                return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="statusBtnPaymenttype" data-id="' +
                                    data.id + '">Active</button>';
                            } else {
                                return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="statusBtnPaymenttype" data-id="' +
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


            //add store

            $('#AddPaymenttype').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    uploadUrl: '{{ route('paymenttypes.store') }}',
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        $('#paymentTypeName').val('');
                        swal({
                            title: "Success!",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        paymenttypeinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }
                });
            });

            //edit store

            $(document).on('click', '#editPaymenttypeBtn', function() {
                let paymenttypeId = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: 'paymenttypes/' + paymenttypeId + '/edit',

                    success: function(data) {
                        $('#EditPaymenttype').find('#editpaymentTypeName').val(data
                            .paymentTypeName);
                        $('#previmg').html('');
                        $('#previmg').append(`
                            <img  src="../` + data.icon + `" alt = "" style="height: 80px" />
                        `);
                        $('#EditPaymenttype').find('#idhidden').val(data.id);
                        $('#EditPaymenttype').attr('data-id', data.id);
                    },
                    error: function(error) {
                        console.log('error');
                    }

                });
            });

            //update store
            $('#EditPaymenttype').submit(function(e) {
                e.preventDefault();
                let paymenttypeId = $('#idhidden').val();

                $.ajax({
                    type: 'POST',
                    url: 'paymenttype/' + paymenttypeId,
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        $('#editpaymentTypeName').val('');

                        swal({
                            title: "Paymenttype update successfully !",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        paymenttypeinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }
                });
            });

            //delete store

            $(document).on('click', '#deletePaymenttypeBtn', function() {
                let paymenttypeId = $(this).data('id');
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
                                url: 'paymenttypes/' + paymenttypeId,

                                success: function(data) {
                                    swal("Poof! Your paymenttype has been deleted!", {
                                        icon: "success",
                                    });
                                    paymenttypeinfotbl.ajax.reload();
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

            //status update store

            $(document).on('click', '#statusBtnPaymenttype', function() {
                let paymenttypeId = $(this).data('id');
                let paymenttypeStatus = $(this).data('status');

                $.ajax({
                    type: 'PUT',
                    url: 'paymenttype/status',
                    data: {
                        paymenttype_id: paymenttypeId,
                        status: paymenttypeStatus,
                        _token:token
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
                        paymenttypeinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }

                });
            });











        });
    </script>


@endsection
