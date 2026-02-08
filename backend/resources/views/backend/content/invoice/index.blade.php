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
<style>
    #productrqinfo_filter input[type="search"]{
        display: none;
    }
</style>
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
    {{-- edit payment icon --}}
    <div class="modal fade" id="editmainFrd" tabindex="-1">
        <div class="modal-dialog">
            <div class="rounded modal-content bg-secondary h-100">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: red;">Edit Invoice : <span id="invID" style="color:#198754 !important;"></span></h5>
                    <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                        aria-label="Close" style="background-color: red !important;color: white !important;opacity: 1;"></button>
                </div>
                <div class="modal-body">

                    <form name="form" id="EditMenu" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group" hidden>
                            <label for="">Invoice ID</label>
                            <input type="text" name="invoiceID" id="invoiceID" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Reseller ID</label>
                            <input type="text" id="resellerid" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Reseller Package</label>
                            <select class="form-control" name="resellerpackage" id="resellerpackage">
                                @forelse (App\Models\Package::where('status','Active')->get() as $pack)
                                    <option value="{{ $pack->id }}">{{ $pack->package_name }}</option>
                                @empty

                                @endforelse
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Package Amount</label>
                            <input type="text" name="amount" id="amount" class="form-control" readonly style="background-color: #e9ecef !important;">
                        </div>
                        <div class="form-group">
                            <label for="">Paid Amount</label>
                            <input type="text" name="paidamount" id="paidamount" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Discount</label>
                            <input type="text" name="discount" id="discount" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Transection ID</label>
                            <input type="text" name="payment_id" id="payment_id" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Payment Type Name</label>
                            <input type="text" name="payment_type" id="payment_type" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="">Account Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="Choose">Choose</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Paid">Paid</option>
                                <option value="Cancel">Cancel</option>
                                <option value="Ban">Ban</option>
                            </select>
                        </div>
                        <div class="mt-3 mb-2 row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="">Blocking Reason</label>
                                    <textarea name="blocking_reason" id="blocking_reason" rows="3" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="">From</label>
                                    <input type="date" name="from_date" id="from_date" class="form-control datepicker">
                                </div>
                                <div class="form-group">
                                    <label for="">To</label>
                                    <input type="date" name="to_date" id="to_date" class="form-control datepicker">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">Referral Bonus %</label>
                            <input type="text" name="bonus_percent" id="bonus_percent" class="form-control">
                        </div>
                        <input type="text" name="inv_id" id="inv_id" hidden>

                        <br>
                        <div class="mt-2 form-group" style="text-align: right">
                            <div class="submitBtnSCourse d-flex justify-content-between">
                                <button type="submit" name="btn" data-bs-dismiss="modal"
                                    class="btn btn-dark" style="float: left">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary AddCourierBtn">Update</button>
                            </div>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div><!-- End popup Modal-->

    {{-- //table section for category --}}
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="mb-3 d-flex justify-content-end">
                    <div class="p-2 form-group col-md-2">
                        <label for="inputCity" class="col-form-label">Start Date</label>
                        <input type="date" class="form-control datepicker" id="startDate" value="<?php echo date('Y-m-d', strtotime('-5 days')); ?>" placeholder="Select Date">
                    </div>
                    <div class="p-2 form-group col-md-2">
                        <label for="inputCity" class="col-form-label">End Date</label>
                        <input type="date" class="form-control datepicker" id="endDate" value="<?php echo date('Y-m-d')?>" placeholder="Select Date">
                    </div>

                    <div class="p-2 form-group col-md-2">
                        <label for="inputCity" class="col-form-label">User Phone</label>
                        <input type="text" class="form-control" name="phone" id="phone" placeholder="User Phone">
                    </div>
                </div>
            </div>
            <div class="col-lg-12">

                <div class="card">
                    <div class="pt-4 card-body" style="text-align: center;">
                        @if (\Session::has('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-1"></i>
                                {{ \Session::get('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="buttonsec">
                            <a href="{{ url('resellerinvoice/all') }}" class="text-white btn btn-sm" style="background:#2A74B8;border:1px solid #2A74B8;">All</a>
                            <a href="{{ url('resellerinvoice/Paid') }}" class="text-white btn btn-sm" style="background:#14BF7D;border:1px solid #14BF7D;">Paid</a>
                            <a href="{{ url('resellerinvoice/Unpaid') }}" class="text-white btn btn-sm" style="background:#EB762A;border:1px solid #EB762A;">Unpaid</a>
                            <a href="{{ url('resellerinvoice/Cancel') }}" class="text-white btn btn-sm" style="background:#F00;border:1px solid #F00;">Cencel</a>
                            <a href="{{ url('resellerinvoice/Ban') }}" class="text-white btn btn-sm" style="background:#613EEA;border:1px solid #613EEA;">Ban</a>
                        </div>
                        <!-- Table with stripped rows -->
                        <div class="table-responsive">
                            <table class="table mb-0 table-centered table-borderless table-hover" id="productrqinfo"
                                width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th></th>
                                        <th>User</th>
                                        <th>Phone</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Payment Type</th>
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


    @if ($status)
        <input type="text" class="form-control" name="productrq_status" id="productrq_status"
            value="{{ $status }}" hidden>
    @else
        <input type="text" class="form-control" name="productrq_status" id="productrq_status" value="all" hidden>
    @endif
</div>
</div>
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
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: {
                url: "{{ url('resellerinvoice/data/') }}" + '/' + statusproductrq,
                data: {
                    startDate: function() { return $('#startDate').val() },
                    endDate: function() { return $('#endDate').val() },
                    phone: function() { return $('#phone').val() }
                }
            },
            columnDefs: [{
                targets: 0,
                checkboxes: {
                    selectRow: false,
                },
            }, ],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'user'
                },
                {
                    data: 'email',
                    searchable: true
                },
                {
                    data: 'package'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'paid_amount',
                },
                {
                    data: 'payment_type',
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },

            ]
        });




        //edit menu
        $(document).on('click', '#editFrdBtn', function() {
            var id = $(this).attr('data-id');

            $.ajax({
                type: 'GET',
                url: "{{ url('resellerinvoice') }}/" + id + "/edit",

                success: function(data) {
                    $('#invID').text(data.invoiceID);
                    $('#EditMenu').find('#invoiceID').val(data.invoiceID);
                    $('#EditMenu').find('#resellerid').val(data.resellerid);
                    $('#EditMenu').find('#resellerpackage').val(data.package_id);
                    $('#EditMenu').find('#inv_id').val(data.id);
                    $('#EditMenu').find('#blocking_reason').text(data.blocking_reason);
                    $('#EditMenu').find('#amount').val(data.amount);
                    $('#EditMenu').find('#from_date').val(data.from_date);
                    $('#EditMenu').find('#to_date').val(data.to_date);
                    $('#EditMenu').find('#paidamount').val(data.paid_amount);
                    $('#EditMenu').find('#bonus_percent').val(data.bonus_percent);
                    $('#EditMenu').find('#discount').val(data.discount);
                    $('#EditMenu').find('#payment_id').val(data.payment_id);
                    $('#EditMenu').find('#payment_type').val(data.payment_type);
                    $('#EditMenu').find('#status').val(data.status);
                    $('#EditMenu').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update menu
        $('#EditMenu').submit(function(e) {
            e.preventDefault();
            let menuId = $('#inv_id').val();

            $.ajax({
                type: 'POST',
                url: 'update/' + menuId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditMenu').find('#invoiceID').val('');
                    $('#EditMenu').find('#inv_id').val('');
                    $('#EditMenu').find('#amount').val('');
                    $('#EditMenu').find('#discount').val('');
                    $('#EditMenu').find('#payment_id').val('');
                    $('#EditMenu').find('#payment_type').val('');
                    $('#EditMenu').find('#status').val('');

                    swal({
                        title: "Invoice request update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    productrqinfotbl.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //delete productrq

        $(document).on('click', '#deleteComplainBtn', function() {
            let productrqsId = $(this).data('id');
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
                            url: 'productrqs/' + productrqsId,

                            success: function(data) {
                                swal("Poof! Your productrq has been deleted!", {
                                    icon: "success",
                                });
                                productrqinfotbl.ajax.reload();
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

        $(document).on('change', '#phone', function(){
            productrqinfotbl.ajax.reload();
        });
        $(document).on('change', '#startDate', function(){
            productrqinfotbl.ajax.reload();
        });
        $(document).on('change', '#endDate', function(){
            productrqinfotbl.ajax.reload();
        });

    });



</script>

@endsection
