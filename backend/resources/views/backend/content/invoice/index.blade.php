@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Invoice
@endsection

@section('subcss')
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
                <li class="breadcrumb-item active">Invoices</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Invoice List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainInvoice" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Invoice
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="invoiceinfo" width="100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Reseller</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- create invoice modal --}}
    <div class="modal fade" id="mainInvoice" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddInvoice" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Product</label>
                                <select name="product_id[]" class="form-select select2" id="product_id" multiple required>
                                    @forelse(App\Models\Product::all() as $product)
                                        <option data-price="{{ $product->selling_price }}" value="{{ $product->id }}">{{ $product->ProductName }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Customer Name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" id="phone" placeholder="Phone" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" id="address" placeholder="Address" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control" name="amount" id="amount" placeholder="Amount" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Delivery Charge</label>
                                <input type="number" class="form-control" name="delivery_charge" id="delivery_charge" placeholder="Delivery Charge" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Payment Type</label>
                                <input type="text" class="form-control" name="payment_type" id="payment_type" placeholder="Payment Type" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Paid Amount</label>
                                <input type="number" class="form-control" name="payment_amount" id="payment_amount" placeholder="Paid Amount" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Courier</label>
                                <select class="form-select" name="courier_id" id="courier_id">
                                    @forelse(App\Models\Courier::all() as $courier)
                                        <option value="{{ $courier->id }}">{{ $courier->curierName }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
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

    {{-- edit invoice modal --}}
    <div class="modal fade" id="editInvoice" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditInvoice" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Customer Name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" id="phone" placeholder="Phone" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" id="address" placeholder="Address" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control" name="amount" id="amount" placeholder="Amount" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Delivery Charge</label>
                                <input type="number" class="form-control" name="delivery_charge" id="delivery_charge" placeholder="Delivery Charge" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Payment Type</label>
                                <input type="text" class="form-control" name="payment_type" id="payment_type" placeholder="Payment Type" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Paid Amount</label>
                                <input type="number" class="form-control" name="payment_amount" id="payment_amount" placeholder="Paid Amount" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Courier</label>
                                <select class="form-select" name="courier_id" id="courier_id">
                                    @forelse(App\Models\Courier::all() as $courier)
                                        <option value="{{ $courier->id }}">{{ $courier->curierName }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>
                        <input type="text" name="invoice_id" id="invoice_id" hidden>
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

@section('subscript')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-datatables-checkboxes@1.2.13/js/dataTables.checkboxes.min.js"></script>
@endsection

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        $('.select2').select2();

        var invoiceinfotbl = $('#invoiceinfo').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: '{{ route('invoicedata.info', $status) }}',
            columnDefs: [{ targets: 0, checkboxes: { selectRow: false } }],
            columns: [
                { data: 'id' },
                { data: 'user', name: 'user', orderable: false, searchable: false },
                { data: 'email', name: 'email', orderable: false, searchable: false },
                { data: 'package', name: 'package', orderable: false, searchable: false },
                { data: 'amount' },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ]
        });

        // select product get price
        var totalAmount = 0;
        $(document).on('change', '#product_id', function() {
            var parent = $(this).closest('form');
            var selects = $(this).find(':selected');
            totalAmount = 0;
            selects.each(function(index, element) {
                totalAmount += parseInt($(element).data('price'));
            });
            parent.find('#amount').val(totalAmount);
        });

        // add invoice
        $('#AddInvoice').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST', url: 'resellerinvoice/store',
                processData: false, contentType: false, data: new FormData(this),
                success: function(data) {
                    $('#name').val(''); $('#phone').val(''); $('#email').val('');
                    swal({ title: "Success!", icon: "success" });
                    invoiceinfotbl.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        // edit invoice
        $(document).on('click', '#editInvoiceBtn', function() {
            let invoiceId = $(this).data('id');
            $.ajax({
                type: 'GET', url: 'invoices/' + invoiceId + '/edit',
                success: function(data) {
                    $('#EditInvoice').find('#name').val(data.name);
                    $('#EditInvoice').find('#phone').val(data.phone);
                    $('#EditInvoice').find('#email').val(data.email);
                    $('#EditInvoice').find('#address').val(data.address);
                    $('#EditInvoice').find('#amount').val(data.amount);
                    $('#EditInvoice').find('#delivery_charge').val(data.delivery_charge);
                    $('#EditInvoice').find('#payment_type').val(data.payment_type);
                    $('#EditInvoice').find('#payment_amount').val(data.payment_amount);
                    $('#EditInvoice').find('#status').val(data.status);
                    $('#EditInvoice').find('#invoice_id').val(data.id);
                    $('#EditInvoice').find('#courier_id').val(data.courier_id);
                    $('#EditInvoice').attr('data-id', data.id);
                },
                error: function(error) { console.log('error'); }
            });
        });

        // update invoice
        $('#EditInvoice').submit(function(e) {
            e.preventDefault();
            let invoiceId = $('#invoice_id').val();
            $.ajax({
                type: 'POST', url: 'invoice/' + invoiceId,
                processData: false, contentType: false, data: new FormData(this),
                success: function(data) {
                    swal({ title: "Invoice update successfully !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    invoiceinfotbl.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        // delete invoice
        $(document).on('click', '#deleteInvoiceBtn', function() {
            let invoiceId = $(this).data('id');
            swal({ title: "Are you sure?", text: "Once deleted, you will not be able to recover this !", icon: "warning", buttons: true, dangerMode: true })
                .then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            type: 'DELETE', url: 'invoices/' + invoiceId, data: { '_token': token },
                            success: function(data) { swal("Invoice has been deleted!", { icon: "success" }); invoiceinfotbl.ajax.reload(); },
                            error: function(error) { console.log('error'); }
                        });
                    } else { swal("Your data is safe!"); }
                });
        });
    });
</script>

@endsection
