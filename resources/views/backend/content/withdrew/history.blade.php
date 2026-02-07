@extends('backend.master')

@section('maincontent')

@section('subcss')
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

<style>
    .flatpickr-input { background: white; }
    .card-body .row .col-lg-3 .card { min-height: 80px; }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <section class="section">
                <div class="row">
                    <div class="col-lg-12">

                        <!-- User Info Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="sideinfo">
                                        <h4 class="p-0 m-0 fw-bold" style="font-size: 18px;">{{ $user->name }}</h4>
                                        <p class="p-0 m-0 text-muted" style="font-size:13px;">{{ $user->email }}</p>
                                        <p class="p-0 m-0 text-muted" style="font-size:13px;">SHOP: {{ $user->shop_name ?? 'N/A' }}</p>
                                        <p class="p-0 m-0 text-muted" style="font-size:13px;">ID: {{ $user->my_referral_code ?? 'N/A' }}</p>
                                    </div>
                                    <div class="text-end">
                                        <img src="{{ asset($user->profile ?? 'backend/img/default-avatar.png') }}"
                                             alt="Profile" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                </div>
                            </div>

                             <!-- Summary Boxes -->
                            <div class="card-body pt-4">
                                <div class="row text-center text-lg-start">
                                    <div class="col-6 col-lg-3 mb-3">
                                        <div class="card card-body bg-red text-white">
                                            <h6>Current Balance</h6>
                                            <h5 class="m-0">৳ {{ number_format($user->account_balance, 2) }}</h5>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-3 mb-3">
                                        <div class="card card-body bg-warning text-white">
                                            <h6>Pending Withdrawal</h6>
                                            <h5 class="m-0">৳ {{ number_format($user->pending_cashout_balance ?? 0, 2) }}</h5>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-3 mb-3">
                                        <div class="card card-body bg-success text-white">
                                            <h6>Total Withdrawn</h6>
                                            <h5 class="m-0">৳ {{ number_format($user->cashout_balance ?? 0, 2) }}</h5>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-3 mb-3">
                                        <div class="card card-body bg-red text-white">
                                            <h6>Total Income</h6>
                                            <h5 class="m-0">৳ {{ number_format($incomes->sum('amount'), 2) }}</h5>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-3 mb-3">
                                        <div class="card card-body bg-danger text-white">
                                            <h6>Charge / Deduct</h6>
                                            <h5 class="m-0">৳ {{ number_format($chargededucts->sum('amount'), 2) }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Orders History Table (Main Income Source) -->
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0">Order History – Seller Income Details</h5>
                            </div>
                            <div class="card-body">
                                <!-- Filters -->
                                <div class="row mb-4">
                                    <div class="col-md-5">
                                        <label class="form-label">Date Range</label>
                                        <input type="text" id="date_range" class="form-control" placeholder="Select date range">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select id="status_filter" class="form-control select2">
                                            <option value="">All Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Processing">Processing</option>
                                            <option value="Delivered">Delivered</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button id="clear_filters" class="btn btn-secondary">Clear Filters</button>
                                    </div>
                                </div>

                                <!-- DataTable -->
                                <table id="orders_table" class="table table-striped table-hover" style="width:100%">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Order Date</th>
                                            <th>Invoice ID</th>
                                            <th>Sub Total</th>
                                            <th>Delivery Charge</th>
                                            <th>Discount</th>
                                            <th>Payment Amount</th>
                                            <th>Profit (Income)</th>
                                            <th>Bonus</th>
                                            <th>Status</th>
                                            <th>Payment Method</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

@section('subscript')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
@endsection

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Date range picker
        const datePicker = flatpickr("#date_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M, Y"
        });

        // DataTable
        var table = $('#orders_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.incomehistory.orders', $user->id) }}",
                data: function(d) {
                    if (datePicker.selectedDates.length === 2) {
                        d.from_date = flatpickr.formatDate(datePicker.selectedDates[0], "Y-m-d");
                        d.to_date   = flatpickr.formatDate(datePicker.selectedDates[1], "Y-m-d");
                    }
                    d.status = $('#status_filter').val();
                }
            },
            columns: [
                { data: 'orderDate', name: 'orderDate' },
                { data: 'invoiceID', name: 'invoiceID' },
                { data: 'subTotal', name: 'subTotal', render: function(data) { return '৳ ' + Number(data).toLocaleString(); } },
                { data: 'deliveryCharge', name: 'deliveryCharge', render: function(data) { return data ? '৳ ' + Number(data).toLocaleString() : '৳ 0'; } },
                { data: 'discountCharge', name: 'discountCharge', render: function(data) { return data ? '৳ ' + Number(data).toLocaleString() : '৳ 0'; } },
                { data: 'paymentAmount', name: 'paymentAmount', render: function(data) { return data ? '৳ ' + Number(data).toLocaleString() : '৳ 0'; } },
                { data: 'profit', name: 'profit',
  render: function(data, type, row) {
      if (data === null || data === undefined || data === '') return '৳ 0.00';
      var num = parseFloat(data);
      return isNaN(num) ? '৳ 0.00' : '৳ ' + num.toFixed(2);
  }
},
{ data: 'order_bonus', name: 'order_bonus',
  render: function(data, type, row) {
      if (data === null || data === undefined || data === '') return '৳ 0.00';
      var num = parseFloat(data);
      return isNaN(num) ? '৳ 0.00' : '৳ ' + num.toFixed(2);
  }
},
                { data: 'status', name: 'status',
                  render: function(data) {
                      let badge = 'warning';
                      if (data === 'Completed' || data === 'Delivered') badge = 'success';
                      else if (data === 'Processing') badge = 'info';
                      else if (data === 'Cancelled') badge = 'danger';
                      else if (data === 'Pending') badge = 'warning';
                      return `<span class="badge bg-${badge}">${data}</span>`;
                  }
                },
                { data: 'Payment', name: 'Payment' }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });

        // Apply filters
        $('#date_range, #status_filter').on('change', function() {
            table.draw();
        });

        // Clear filters button
        $('#clear_filters').on('click', function() {
            datePicker.clear();
            $('#status_filter').val('').trigger('change');
            table.draw();
        });
    });
</script>

@endsection