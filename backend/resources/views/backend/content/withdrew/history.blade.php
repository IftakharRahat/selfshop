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
</style>

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{url('/admindashboard')}}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('admin/withdrawrequest') }}">Withdrawals</a></li>
                <li class="breadcrumb-item active">Income History — {{ $user->name }}</li>
            </ol>
        </nav>
    </div>

    {{-- User Info Card --}}
    <div class="admin-content-card">
        <div class="admin-card-header">
            <div>
                <h6 class="admin-card-title mb-1">
                    <i class="bi bi-person-circle me-2"></i>{{ $user->name }}
                </h6>
                <div class="text-muted" style="font-size: 13px;">
                    {{ $user->email }}
                    @if($user->shop_name) &nbsp;•&nbsp; Shop: {{ $user->shop_name }} @endif
                    @if($user->my_referral_code) &nbsp;•&nbsp; ID: {{ $user->my_referral_code }} @endif
                </div>
            </div>
            <div>
                <img src="{{ asset($user->profile ?? 'backend/img/default-avatar.png') }}"
                     alt="Profile" class="rounded-circle" style="width: 56px; height: 56px; object-fit: cover; border: 2px solid var(--admin-border);">
            </div>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div style="background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 10px; padding: 16px; color: #fff;">
                        <div style="font-size: 12px; opacity: 0.85; margin-bottom: 4px;">Current Balance</div>
                        <div style="font-size: 18px; font-weight: 700;">৳ {{ number_format($user->account_balance, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div style="background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 10px; padding: 16px; color: #fff;">
                        <div style="font-size: 12px; opacity: 0.85; margin-bottom: 4px;">Pending Withdrawal</div>
                        <div style="font-size: 18px; font-weight: 700;">৳ {{ number_format($user->pending_cashout_balance ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div style="background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 10px; padding: 16px; color: #fff;">
                        <div style="font-size: 12px; opacity: 0.85; margin-bottom: 4px;">Total Withdrawn</div>
                        <div style="font-size: 18px; font-weight: 700;">৳ {{ number_format($user->cashout_balance ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div style="background: linear-gradient(135deg, #6366f1, #4f46e5); border-radius: 10px; padding: 16px; color: #fff;">
                        <div style="font-size: 12px; opacity: 0.85; margin-bottom: 4px;">Total Income</div>
                        <div style="font-size: 18px; font-weight: 700;">৳ {{ number_format($incomes->sum('amount'), 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div style="background: linear-gradient(135deg, #e11d48, #be123c); border-radius: 10px; padding: 16px; color: #fff;">
                        <div style="font-size: 12px; opacity: 0.85; margin-bottom: 4px;">Charge / Deduct</div>
                        <div style="font-size: 18px; font-weight: 700;">৳ {{ number_format($chargededucts->sum('amount'), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Orders History Table --}}
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">
                <i class="bi bi-receipt me-2"></i>Order History – Seller Income Details
            </h6>
        </div>
        <div class="admin-card-body">
            {{-- Filters --}}
            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label">Date Range</label>
                    <input type="text" id="date_range" class="form-control" placeholder="Select date range">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select id="status_filter" class="form-select select2">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="clear_filters" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                    </button>
                </div>
            </div>

            {{-- DataTable --}}
            <div class="table-responsive">
                <table id="orders_table" class="table admin-table" style="width:100%">
                    <thead>
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