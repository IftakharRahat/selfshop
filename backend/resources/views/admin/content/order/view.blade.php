@extends('backend.master')

@section('maincontent')

<style>
    .process-steps {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        justify-content: space-between;
    }
    .process-steps li {
        flex: 1;
        text-align: center;
        position: relative;
    }
    .process-steps li .icon {
        height: 32px;
        width: 32px;
        margin: auto;
        background: #f3f4f6;
        border-radius: 50%;
        line-height: 32px;
        font-size: 13px;
        font-weight: 700;
        color: #9ca3af;
        position: relative;
        z-index: 1;
        border: 2px solid #e5e7eb;
    }
    .process-steps li .icon.active {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    .process-steps li .title {
        font-weight: 500;
        font-size: 11px;
        color: #9ca3af;
        margin-top: 6px;
        margin-bottom: 0;
    }
    .process-steps li .title.active {
        color: #2563eb;
        font-weight: 600;
    }
    .process-steps li+li:after {
        position: absolute;
        content: "";
        height: 2px;
        width: calc(100% - 32px);
        background: #e5e7eb;
        top: 16px;
        z-index: 0;
        right: calc(50% + 16px);
    }
    .order-detail-label {
        font-weight: 600;
        color: #374151;
        font-size: 13px;
    }
    .order-detail-value {
        color: #6b7280;
        font-size: 13px;
    }
</style>

    <?php
    use App\Models\Admin;
    $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();
    $users = Admin::whereHas('roles', function ($q) {
        $q->where('name', 'user');
    })
        ->where('status', 'Active')
        ->inRandomOrder()
        ->get();
    ?>
    <div class="container-fluid pt-4 px-4">

        <div class="pagetitle row mb-3">
            <div class="col-12">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">View Order</li>
                    </ol>
                </nav>
            </div>
        </div>

        @if (isset($orders))
            {{-- Invoice & Progress Tracker --}}
            <div class="admin-content-card mb-3">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Invoice #{{ $orders->invoiceID }}</h6>
                    <span class="badge bg-{{ $orders->status == 'Delivered' ? 'success' : ($orders->status == 'Canceled' ? 'danger' : ($orders->status == 'Pending' ? 'warning' : 'primary')) }}">
                        {{ $orders->status }}
                    </span>
                </div>
                <div class="admin-card-body">
                    <ul class="process-steps clearfix mb-4">
                        @php
                            $statuses = ['Pending', 'Confirmed', 'Processing', 'Packageing', 'Ontheway', 'Delivered', 'Canceled', 'Return'];
                            $labels = ['Pending', 'Confirmed', 'Processing', 'Packaging', 'On the Way', 'Delivered', 'Canceled', 'Return'];
                        @endphp
                        @foreach($statuses as $i => $s)
                            <li>
                                <div class="icon {{ $orders->status == $s ? 'active' : '' }}">{{ $i + 1 }}</div>
                                <div class="title {{ $orders->status == $s ? 'active' : '' }}">{{ $labels[$i] }}</div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="row">
                        <div class="col-lg-6">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="order-detail-label" style="width: 40%">Order ID</td>
                                        <td class="order-detail-value">{{ $orders->invoiceID }}</td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Customer</td>
                                        <td class="order-detail-value">{{ $orders->customers?->customerName ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Phone</td>
                                        <td class="order-detail-value">{{ $orders->customers?->customerPhone ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Shipping Address</td>
                                        <td class="order-detail-value">{{ $orders->customers?->customerAddress ?? '—' }}@if (isset($orders->zones)), {{ $orders->zones->zoneName }}@endif @if (isset($orders->cities)), {{ $orders->cities->cityName }}@endif</td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Shipping Company</td>
                                        <td class="order-detail-value">
                                            @if (isset($orders->couriers))
                                                {{ $orders->couriers->courierName }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="order-detail-label" style="width: 40%">Order Date</td>
                                        <td class="order-detail-value">
                                            @if($orders->created_at)
                                                {{ $orders->created_at->format('Y-m-d') }}, {{ $orders->created_at->format('h:i A') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Internal Status</td>
                                        <td class="order-detail-value">{{ $orders->status ?? 'Pending' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Customer View Status</td>
                                        <td class="order-detail-value">{{ $orders->customer_status ?? $orders->status ?? 'Pending' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Courier Live Status</td>
                                        <td class="order-detail-value">{{ $orders->steadfast_status ?? 'Not synced yet' }}</td>
                                    </tr>
                                    @php
                                        $subTotal = (float) ($orders->subTotal ?? 0);
                                        $deliveryCharge = (float) ($orders->deliveryCharge ?? 0);
                                        $discountCharge = (float) ($orders->discountCharge ?? 0);
                                        $advanceDelivery = (int) ($orders->advance_delivery ?? 0);
                                        $totalDue = $subTotal - $discountCharge;
                                        if ($advanceDelivery === 0) {
                                            $totalDue += $deliveryCharge;
                                        }
                                    @endphp
                                    <tr>
                                        <td class="order-detail-label">Total Amount</td>
                                        <td class="order-detail-value">
                                            ৳ {{ number_format($totalDue, 2) }}
                                            @if ($advanceDelivery === 0)
                                                + <span class="text-danger">(Charge: {{ $deliveryCharge }} ৳)</span>
                                            @else
                                                <span class="text-success">(Delivery paid in advance)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Payment Method</td>
                                        <td class="order-detail-value">
                                            @if ($orders->Payment == 'C-O-D')
                                                Cash On Delivery
                                            @else
                                                Online Payment
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Paid</td>
                                        <td class="order-detail-value">
                                            @if($orders->paymentAmount>0)
                                                {{$orders->paymentAmount}} TK
                                            @else
                                                00 TK
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="order-detail-label">Due</td>
                                        <td class="order-detail-value">{{ number_format($totalDue, 2) }} TK</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order Products --}}
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Order Items</h6>
                </div>
                <div class="admin-card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            @forelse ($orders->orderproducts as $products)
                                <tr>
                                    <td class="order-detail-label" style="width: 40%">{{ $products->productName }}</td>
                                    <td class="order-detail-value"><span class="text-danger">{{ $products->quantity }} pcs</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted">No products found.</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td class="order-detail-label">Completed By</td>
                                <td class="order-detail-value">{{ $orders->admins?->name ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Profit & Commission Breakdown --}}
            <div class="admin-content-card mt-3" style="border: 2px solid #3b82f6; border-radius: 10px; overflow: hidden;">
                <div style="background: #fff; padding: 16px 20px; border-bottom: 3px solid #3b82f6; display: flex; align-items: center; gap: 10px;">
                    <span style="background: #3b82f6; color: #fff; width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fas fa-chart-pie"></i>
                    </span>
                    <h5 style="margin: 0; font-weight: 700; color: #1e293b; font-size: 18px;">Profit &amp; Commission Breakdown</h5>
                </div>
                <div class="admin-card-body p-0">
                    @php
                        $commissionService = app(\App\Services\VendorCommissionService::class);
                        $grandSellingPrice = 0;
                        $grandBasePrice = 0;
                        $grandStorefrontPrice = 0;
                        $grandAdminCommission = 0;
                        $grandSupplierShare = 0;
                        $grandResellerProfit = 0;

                        // ── Pre-compute using ORDER-TIME prices (frozen at checkout).
                        //    $op->productPrice = storefront price at checkout (variant-aware, with commission).
                        //    We reverse-calculate the supplier base price from the frozen storefront price
                        //    by dividing out the commission rate. This ensures that if a supplier later
                        //    changes their price, historical orders still show correct breakdowns.
                        $precomputedLines = [];
                        $totalStorefrontCost = 0;
                        foreach ($orders->orderproducts as $op) {
                            $product = $op->product;
                            $qty = (int) $op->quantity;

                            // Storefront price = frozen at checkout in $op->productPrice
                            $storefrontPrice = (float) ($op->productPrice ?? 0);
                            $commissionRate = 0;

                            // Reverse-calculate the supplier's base price from the frozen storefront price
                            if ($product && $product->vendor_id) {
                                $commissionRate = $commissionService->getRateForProduct($product->vendor_id, $product->category_id);
                                // storefrontPrice = basePrice × (1 + rate/100)
                                // ∴ basePrice = storefrontPrice / (1 + rate/100)
                                $basePrice = $commissionRate > 0
                                    ? round($storefrontPrice / (1 + $commissionRate / 100), 2)
                                    : $storefrontPrice;
                            } else {
                                // Non-vendor / admin product: no commission split
                                $basePrice = $storefrontPrice;
                            }

                            $lineStorefront = round($storefrontPrice * $qty, 2);
                            $totalStorefrontCost += $lineStorefront;

                            $precomputedLines[] = [
                                'op' => $op,
                                'product' => $product,
                                'qty' => $qty,
                                'basePrice' => $basePrice,
                                'storefrontPrice' => $storefrontPrice,
                                'commissionRate' => $commissionRate,
                                'lineStorefront' => $lineStorefront,
                            ];
                        }

                        // The order's subTotal = what the customer actually paid (storefront + reseller markup)
                        // Distribute it proportionally across line items based on each line's storefront share
                        $orderSubTotal = (float) ($orders->subTotal ?? 0);
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size: 13px;">
                            <thead style="background: #f1f5f9;">
                                <tr>
                                    <th style="padding: 10px 12px; font-weight: 600; color: #475569;">Product</th>
                                    <th class="text-center" style="padding: 10px 8px; font-weight: 600; color: #475569;">Qty</th>
                                    <th class="text-end" style="padding: 10px 8px; font-weight: 600; color: #475569;">Base Price<br><small class="text-muted fw-normal">(Supplier's price)</small></th>
                                    <th class="text-end" style="padding: 10px 8px; font-weight: 600; color: #475569;">Storefront Price<br><small class="text-muted fw-normal">(After commission)</small></th>
                                    <th class="text-end" style="padding: 10px 8px; font-weight: 600; color: #475569;">Reseller Sold At<br><small class="text-muted fw-normal">(Customer paid)</small></th>
                                    <th class="text-end" style="padding: 10px 8px; font-weight: 600; color: #dc2626;">Admin Commission<br><small class="fw-normal">(Platform fee)</small></th>
                                    <th class="text-end" style="padding: 10px 8px; font-weight: 600; color: #7c3aed;">Supplier Gets<br><small class="fw-normal">(Base × Qty)</small></th>
                                    <th class="text-end" style="padding: 10px 8px; font-weight: 600; color: #059669;">Reseller Profit<br><small class="fw-normal">(Sold − Storefront)</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($precomputedLines as $lineData)
                                    @php
                                        $op = $lineData['op'];
                                        $product = $lineData['product'];
                                        $qty = $lineData['qty'];
                                        $basePrice = $lineData['basePrice'];
                                        $storefrontPrice = $lineData['storefrontPrice'];
                                        $commissionRate = $lineData['commissionRate'];
                                        $lineStorefront = $lineData['lineStorefront'];

                                        // Reseller Sold At = what the customer actually paid for this line.
                                        // orders.subTotal includes the reseller's markup (orders.profit).
                                        // Distribute proportionally based on each line's storefront share.
                                        if ($totalStorefrontCost > 0 && $orderSubTotal > 0) {
                                            $lineSellingPrice = round(($lineStorefront / $totalStorefrontCost) * $orderSubTotal, 2);
                                        } else {
                                            $lineSellingPrice = $lineStorefront;
                                        }
                                        $resellerSoldAt = $qty > 0 ? round($lineSellingPrice / $qty, 2) : 0;

                                        // Admin commission per unit = storefront - base
                                        $adminCommissionPerUnit = round($storefrontPrice - $basePrice, 2);

                                        // Line totals
                                        $lineBaseCost = round($basePrice * $qty, 2);
                                        $lineAdminCommission = round($adminCommissionPerUnit * $qty, 2);
                                        $lineSupplierShare = $lineBaseCost;
                                        $lineResellerProfit = round($lineSellingPrice - $lineStorefront, 2);

                                        $grandBasePrice += $lineBaseCost;
                                        $grandStorefrontPrice += $lineStorefront;
                                        $grandSellingPrice += $lineSellingPrice;
                                        $grandAdminCommission += $lineAdminCommission;
                                        $grandSupplierShare += $lineSupplierShare;
                                        $grandResellerProfit += $lineResellerProfit;
                                    @endphp
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 10px 12px;">
                                            <strong>{{ $op->productName }}</strong>
                                            @if($product && $product->vendor_id)
                                                <br><small class="text-muted">Vendor product · {{ $commissionRate }}% comm.</small>
                                            @else
                                                <br><small class="text-muted">Admin/Direct product</small>
                                            @endif
                                        </td>
                                        <td class="text-center" style="padding: 10px 8px;">{{ $qty }}</td>
                                        <td class="text-end" style="padding: 10px 8px;">৳{{ number_format($basePrice, 2) }}</td>
                                        <td class="text-end" style="padding: 10px 8px;">৳{{ number_format($storefrontPrice, 2) }}</td>
                                        <td class="text-end" style="padding: 10px 8px; font-weight: 600;">৳{{ number_format($resellerSoldAt, 2) }}</td>
                                        <td class="text-end" style="padding: 10px 8px; color: #dc2626;">৳{{ number_format($lineAdminCommission, 2) }}</td>
                                        <td class="text-end" style="padding: 10px 8px; color: #7c3aed;">৳{{ number_format($lineSupplierShare, 2) }}</td>
                                        <td class="text-end" style="padding: 10px 8px; color: {{ $lineResellerProfit >= 0 ? '#059669' : '#dc2626' }}; font-weight: 600;">
                                            {{ $lineResellerProfit >= 0 ? '৳' : '-৳' }}{{ number_format(abs($lineResellerProfit), 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted text-center py-3">No products found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                                <tr style="font-weight: 700; font-size: 14px;">
                                    <td style="padding: 12px;" colspan="2"><strong>ORDER TOTALS</strong></td>
                                    <td class="text-end" style="padding: 12px 8px;">৳{{ number_format($grandBasePrice, 2) }}</td>
                                    <td class="text-end" style="padding: 12px 8px;">৳{{ number_format($grandStorefrontPrice, 2) }}</td>
                                    <td class="text-end" style="padding: 12px 8px;">৳{{ number_format($grandSellingPrice, 2) }}</td>
                                    <td class="text-end" style="padding: 12px 8px; color: #dc2626;">৳{{ number_format($grandAdminCommission, 2) }}</td>
                                    <td class="text-end" style="padding: 12px 8px; color: #7c3aed;">৳{{ number_format($grandSupplierShare, 2) }}</td>
                                    <td class="text-end" style="padding: 12px 8px; color: {{ $grandResellerProfit >= 0 ? '#059669' : '#dc2626' }};">
                                        {{ $grandResellerProfit >= 0 ? '৳' : '-৳' }}{{ number_format(abs($grandResellerProfit), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Summary Cards --}}
                    <div class="row g-3 p-3" style="border-top: 1px solid #e2e8f0;">
                        <div class="col-md-3">
                            <div style="background: #fef2f2; border-radius: 8px; padding: 16px; text-align: center;">
                                <div style="font-size: 11px; font-weight: 600; color: #991b1b; text-transform: uppercase; letter-spacing: 0.05em;">Admin Commission</div>
                                <div style="font-size: 22px; font-weight: 700; color: #dc2626; margin-top: 4px;">৳{{ number_format($grandAdminCommission, 2) }}</div>
                                <div style="font-size: 11px; color: #6b7280;">Platform earning</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="background: #f5f3ff; border-radius: 8px; padding: 16px; text-align: center;">
                                <div style="font-size: 11px; font-weight: 600; color: #5b21b6; text-transform: uppercase; letter-spacing: 0.05em;">Supplier Share</div>
                                <div style="font-size: 22px; font-weight: 700; color: #7c3aed; margin-top: 4px;">৳{{ number_format($grandSupplierShare, 2) }}</div>
                                <div style="font-size: 11px; color: #6b7280;">Vendor receives</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="background: #ecfdf5; border-radius: 8px; padding: 16px; text-align: center;">
                                <div style="font-size: 11px; font-weight: 600; color: #065f46; text-transform: uppercase; letter-spacing: 0.05em;">Reseller Profit</div>
                                <div style="font-size: 22px; font-weight: 700; color: {{ $grandResellerProfit >= 0 ? '#059669' : '#dc2626' }}; margin-top: 4px;">
                                    {{ $grandResellerProfit >= 0 ? '৳' : '-৳' }}{{ number_format(abs($grandResellerProfit), 2) }}
                                </div>
                                <div style="font-size: 11px; color: #6b7280;">Reseller margin</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="background: #eff6ff; border-radius: 8px; padding: 16px; text-align: center;">
                                <div style="font-size: 11px; font-weight: 600; color: #1e40af; text-transform: uppercase; letter-spacing: 0.05em;">Order Profit (Legacy)</div>
                                <div style="font-size: 22px; font-weight: 700; color: #2563eb; margin-top: 4px;">৳{{ number_format((float)($orders->profit ?? 0), 2) }}</div>
                                <div style="font-size: 11px; color: #6b7280;">orders.profit field</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
        {{-- //user role --}}
        @if ($admin->hasrole('user'))
            <input type="text" id="user_role" value="0" hidden>
        @else
            <input type="text" id="user_role" value="1" hidden>
        @endif

        @if (empty($status))
        @else
            <input type="text" id="orderstatus" value="{{ $status }}" hidden>
        @endif
    </div>



    <script>
        $(document).ready(function() {
            var token = $("input[name='_token']").val();

            var orderstatus = $('#orderstatus').val();
            var user_role = $('#user_role').val();

            //assign user
            $(document).on('click', '.assign-user', function(e) {
                e.preventDefault();

                var rows_selected = orderinfotbl.column(0).checkboxes.selected();
                var ids = [];
                $.each(rows_selected, function(index, rowId) {
                    ids[index] = rowId;
                });
                var user_id = $(this).attr('data-id');

                jQuery.ajax({
                    type: "get",
                    url: "{{ url('admin_order/assign_user') }}",
                    contentType: "application/json",
                    data: {
                        action: "assign",
                        ids: ids,
                        user_id: user_id
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data["status"] == "success") {
                            swal(data["message"]);
                            orderinfotbl.ajax.reload();
                        } else {
                            if (data["status"] == "failed") {
                                swal(data["message"]);
                            } else {
                                swal("Something wrong ! Please try again.");
                            }
                        }
                    }
                });

            });

            // update status selected item

            $(document).on('click', '.btn-change-status', function(e) {
                e.preventDefault();
                var rows_selected = orderinfotbl.column(0).checkboxes.selected();
                var ids = [];
                $.each(rows_selected, function(index, rowId) {
                    ids[index] = rowId;
                });
                var status = $(this).attr('data-status');
                $.ajax({
                    type: "get",
                    url: "{{ url('admin_order/statusUpdateByCheckbox') }}",
                    data: {
                        'status': status,
                        'orders_id': ids,
                        '_token': token
                    },
                    success: function(response) {
                        countorder();
                        var data = JSON.parse(response);
                        if (data['status'] == 'success') {
                            toastr.success(data["message"]);
                            orderinfotbl.ajax.reload();
                        } else {
                            if (data['status'] == 'failed') {
                                toastr.error(data["message"]);
                            } else {
                                toastr.error('Something wrong ! Please try again.');
                            }
                        }
                    }
                });
            });

            $('#orderinfo thead th').each(function() {
                //count orders
                countorder();
                var title = $(this).text();
                if (title != 'Status' &&
                    title != '' &&
                    title != 'Action' &&
                    title != 'Products' &&
                    title != 'Total') {
                    // console.log(title);
                    if (title == 'Order Date') {
                        $(this).html(
                            '<input type="text" style="width: 60px;" class="form-control datepicker" id="dateorder" placeholder="Date" />'
                        );
                    }

                    if (title == 'Courier') {
                        $(this).html(
                            ' <select type="text" class="form-control courierID" id="courierID" style="width: 140px;  placeholder="Courier" ></select>'
                        );
                    }
                    if (title == 'User') {
                        $(this).html(
                            ' <select type="text" style="width: 100px;" class="form-control" id="userID" placeholder="Reseller" ></select>'
                        );
                    }
                    if (title == 'Invoice ID') {
                        $(this).html(
                            ' <input type="text" class="form-control cuID" placeholder="User ID" />');
                    }
                    if (title == 'Name') {
                        $(this).html(
                            ' <input type="text" class="form-control" placeholder="Customer Phone" />');
                    }

                }
            });

            $("#userID").select2({
                placeholder: "Select a User",
                allowClear: true,
                ajax: {
                    url: '{{ url('admin_order/users') }}',
                    processResults: function(data) {
                        var data = $.parseJSON(data);
                        return {
                            results: data
                        };
                    }
                }
            });

            $("#courierID").select2({
                placeholder: "Select a Courier",
                allowClear: true,
                ajax: {
                    url: '{{ url('admin_order/courier') }}',
                    processResults: function(data) {
                        var data = $.parseJSON(data);
                        return {
                            results: data
                        };
                    }
                }
            });

            $("#cityID").select2({
                placeholder: "Select a City",
                allowClear: true,
                ajax: {
                    url: '{{ url('admin_order/cities') }}',
                    processResults: function(data) {
                        var data = $.parseJSON(data);
                        return {
                            results: data
                        };
                    }
                }
            });

            orderinfotbl.columns().every(function() {

                var orderinfotbl = this;
                $('input', this.header()).on('keyup change', function() {
                    if (orderinfotbl.search() !== this.value) {
                        orderinfotbl.search(this.value).draw();
                    }
                });

                $('select', this.header()).on('change', function() {
                    if (orderinfotbl.search() !== this.value) {
                        orderinfotbl.search(this.value).draw();
                    }
                });

            });

            function countorder() {
                $.ajax({
                    type: "get",
                    url: "{{ url('admin_order/count') }}",
                    contentType: "application/json",
                    success: function(response) {
                        var data = JSON.parse(response);

                        if (data["status"] == "success") {

                            $('#all').text(data["all"]);
                            $('#pending').text(data["pending"]);
                            $('#canceled').text(data["canceled"]);
                            $('#confirmed').text(data["confirmed"]);
                            $('#invoiced').text(data["invoiced"]);
                            $('#ondelivery').text(data["ondelivery"]);
                            $('#delivered').text(data["delivered"]);
                            $('#return').text(data["return"]);

                        } else {
                            if (data["status"] == "failed") {
                                swal(data["message"]);
                            } else {
                                swal("Something wrong ! Please try again.");
                            }
                        }
                    }
                });
            }

            //change order status
            var token = $("input[name='_token']").val();

            $(document).on('click', '.btn-status', function(e) {
                e.preventDefault();
                var status = $(this).attr('data-status');
                var id = $(this).attr('data-id');
                $.ajax({
                    type: "GET",
                    url: "{{ url('order/admin_order/status') }}",
                    data: {
                        'status': status,
                        'id': id,
                        '_token': token
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        countorder();
                        if (data['status'] == 'success') {
                            toastr.success(data["message"]);
                            orderinfotbl.ajax.reload();
                        } else {
                            if (data['status'] == 'failed') {
                                toastr.error(data["message"]);
                            } else {
                                toastr.error('Something wrong ! Please try again.');
                            }
                        }
                    }
                });
            });

            $(document).on('click', '.btn-editorder', function(e) {
                e.preventDefault();
                var id = $(this).attr('data-id');
                $.ajax({
                    type: "get",
                    url: "{{ url('admin_orders') }}/" + id + "/edit",
                    success: function(response) {
                        $('.modal .modal-body').html('');
                        $('.modal .modal-body').empty().append(response);
                        $('.modal').modal('toggle');
                        $('.modal-footer').hide();

                        $(".datepicker").flatpickr();

                        $("#productID").select2({
                            placeholder: "Select a Product",
                            dropdownParent: $('#productTable'),
                            templateResult: function(state) {
                                if (!state.id) {
                                    return state.text;
                                }
                                var $state = $(
                                    '<span><img width="60px" src="' +
                                    state.image +
                                    '" class="img-flag" /> ' +
                                    state.text +
                                    "</span>"
                                );
                                return $state;
                            },
                            ajax: {
                                type: 'GET',
                                url: '{{ url('admin_order/products') }}',
                                processResults: function(data) {
                                    var data = $.parseJSON(data);
                                    return {
                                        results: data.data
                                    };
                                }
                            }
                        }).trigger("change").on("select2:select", function(e) {
                            $("#productTable tbody").append(
                                "<tr>" +
                                '<td  style="display: none"><input type="text" class="productID" style="width:80px;" value="' +
                                e.params.data.id + '"></td>' +
                                '<td><input type="text" name="color" id="ProductColor" value="" style="    max-width: 60px;"> </td>' +
                                '<td><input type="text" name="size" id="ProductSize" value="" style="    max-width: 40px;"></td>' +
                                '<td><span class="productCode">' + e.params.data
                                .productCode + '</span></td>' +
                                '<td><span class="productName">' + e.params.data
                                .text + '</span></td>' +
                                '<td><input type="number" class="productQuantity form-control" style="width:80px;" value="1"></td>' +
                                '<td><input type="number" class="productPrice form-control" style="width:80px;" value="' + e.params.data
                                .productPrice + '"></td>' +
                                '<td><button class="btn btn-sm btn-danger delete-btn"><i class="fa fa-trash"></i></button></td>\n' +
                                "</tr>"
                            );
                            calculation();
                        });


                        $("#courierID").select2({
                            placeholder: "Select a Courier",
                            allowClear: true,
                            dropdownParent: $('#courierdatatbl'),
                            ajax: {
                                url: '{{ url('admin_order/couriers') }}',
                                processResults: function(data) {
                                    var data = $.parseJSON(data);
                                    return {
                                        results: data
                                    };
                                }
                            }
                        }).trigger("change").on("select2:select", function(e) {
                            $("#zoneID").empty();
                            for (var i = 0; i < couriers.length; i++) {
                                if (couriers[i]['courierName'] == e.params.data.text) {
                                    if (couriers[i]['hasCity'] == 'on') {
                                        jQuery(".hasCity").show();
                                    } else {
                                        jQuery(".hasCity").hide();
                                    }
                                    if (couriers[i]["hasZone"] == 'on') {
                                        jQuery(".hasZone").show();
                                    } else {
                                        jQuery(".hasZone").hide();
                                        $("#zoneID").empty();
                                    }
                                }

                                if (e.params.data.text == 'Pathao') {
                                    $("#cityID").empty().append(
                                        '<option value="8">Dhaka</option>');
                                } else {
                                    $("#cityID").empty();
                                }
                            }

                        });

                        if ($("#courierID").text()) {
                            var courier = $("#courierID").text().trim();
                            for (var i = 0; i < couriers.length; i++) {
                                if (couriers[i]['courierName'] == courier) {
                                    if (couriers[i]['hasCity'] == 'on') {
                                        jQuery(".hasCity").show();
                                    } else {
                                        jQuery(".hasCity").hide();
                                    }

                                    if (couriers[i]["hasZone"] == 'on') {
                                        jQuery(".hasZone").show();
                                    } else {
                                        jQuery(".hasZone").hide();
                                        $("#zoneID").empty();
                                    }
                                }
                            }
                        }

                        $("#cityID").select2({
                            placeholder: "Select a City",
                            dropdownParent: $('#citydatatbl'),
                            allowClear: true,
                            ajax: {
                                data: function(params) {
                                    var query = {
                                        q: params.term,
                                        courierID: $("#courierID").val()
                                    };
                                    return query;
                                },
                                type: 'GET',
                                url: '{{ url('admin_order/cities') }}',
                                processResults: function(data) {
                                    var data = $.parseJSON(data);
                                    return {
                                        results: data
                                    };
                                }
                            }
                        });

                        $("#zoneID").select2({
                            placeholder: "Select a Zone",
                            dropdownParent: $('#xonedatatbl'),
                            allowClear: true,
                            ajax: {
                                data: function(params) {
                                    var query = {
                                        q: params.term,
                                        courierID: $("#courierID").val(),
                                        cityID: $("#cityID").val()
                                    };
                                    return query;
                                },
                                type: 'GET',
                                url: '{{ url('admin_order/zones') }}',
                                processResults: function(data) {
                                    var data = $.parseJSON(data);
                                    return {
                                        results: data
                                    };
                                    console.log(data);
                                }
                            }
                        });


                        var orderCommentTable = $("#orderCommentTable").DataTable({
                            ajax: "{{ url('admin_order/getComment') }}?id=" + $(
                                '#orderCommentTable').attr('data-id'),
                            ordering: false,
                            lengthChange: false,
                            bFilter: false,
                            search: false,
                            info: false,
                            columns: [{
                                    data: "date"
                                },
                                {
                                    data: "comment"
                                },
                                {
                                    data: "name"
                                }
                            ],
                        });

                        var oldOrderTable = $("#oldOrderTable").DataTable({
                            ajax: "{{ url('admin_order/previous_orders') }}?id=" + $(
                                '#oldOrderTable').attr('data-id'),
                            ordering: false,
                            lengthChange: false,
                            bFilter: false,
                            search: false,
                            info: false,
                            columns: [{
                                    data: "invoiceID"
                                },
                                {
                                    data: null,
                                    width: "15%",
                                    render: function(data) {
                                        return '<i class="fas fa-user mr-2 text-grey-dark"></i>' +
                                            data.customerName +
                                            '<br> <i class="fas fa-phone  mr-2 text-grey-dark"></i>' +
                                            data.customerPhone +
                                            '<br><i class="fas fa-map-marker mr-2 text-grey-dark"></i>' +
                                            data.customerAddress;
                                    }
                                },
                                {
                                    data: "products"
                                },
                                {
                                    data: "subTotal"
                                },
                                {
                                    data: "status"
                                },
                                {
                                    data: "name"
                                },
                            ]
                        });

                        $(document).on("click", "#updateComment", function() {
                            var note = $('#comment');
                            var id = $('#btn-update').val();
                            if (note.val() == '') {
                                note.css('border', '1px solid red');
                                return;
                            } else if (id == '') {
                                toastr.success('Something Wrong , Try again ! ');
                                return;
                            } else {
                                $.ajax({
                                    type: "GET",
                                    url: "{{ url('admin_order/updateComment') }}",
                                    data: {
                                        'comment': note.val(),
                                        'id': id,
                                        '_token': token
                                    },
                                    success: function(response) {
                                        var data = JSON.parse(response);
                                        if (data['status'] == 'success') {
                                            toastr.success(data["message"]);
                                            orderCommentTable.ajax.reload();
                                        } else {
                                            if (data['status'] ==
                                                'failed') {
                                                toastr.error(data[
                                                    "message"]);
                                            } else {
                                                toastr.error(
                                                    'Something wrong ! Please try again.'
                                                );
                                            }
                                        }
                                    }
                                });

                            }
                            return;

                        });


                        if ($("#paymentTypeID").text()) {
                            var paymentType = $("#paymentTypeID").val();
                            if (paymentType == "") {
                                $(".paymentID").hide();
                                $(".paymentAgentNumber").hide();
                                $(".paymentAmount").hide();
                            } else {
                                $(".paymentID").show();
                                $(".paymentAgentNumber").show();
                                $(".paymentAmount").show();
                            }
                        }

                        $("#paymentTypeID").select2({
                            placeholder: "Select a payment Type",
                            dropdownParent: $('#paymntidname'),
                            allowClear: true,
                            ajax: {
                                data: function(params) {
                                    return {
                                        q: params.term
                                    };
                                    console.log(params);
                                },
                                url: '{{ url('admin_order/paymenttype') }}',
                                processResults: function(data) {

                                    var data = $.parseJSON(data);
                                    return {
                                        results: data
                                    };
                                }
                            }
                        }).trigger("change").on("select2:select", function(e) {
                            if (e.params.data.text == "") {
                                $(".paymentID").hide();
                                $(".paymentAgentNumber").hide();
                                $(".paymentAmount").hide();
                            } else {
                                $(".paymentID").show();
                                $(".paymentAgentNumber").show();
                                $(".paymentAmount").show();
                            }
                        }).on("select2:unselect", function(e) {
                            $(".paymentID").hide();
                            $(".paymentAgentNumber").hide();
                            $(".paymentAmount").hide();
                            calculation();
                        });

                        $("#paymentID").select2({
                            placeholder: "Select a payment Number",
                            dropdownParent: $('#paymentIDname'),
                            allowClear: true,
                            ajax: {
                                data: function(params) {
                                    return {
                                        q: params.term,
                                        paymentTypeID: $("#paymentTypeID").val(),
                                    };
                                },
                                type: 'GET',
                                url: '{{ url('admin_order/paymentnumber') }}',

                                processResults: function(data) {
                                    var data = $.parseJSON(data);
                                    return {
                                        results: data
                                    };
                                }
                            }
                        });

                        $(document).on("change", ".productQuantity", function() {
                            calculation();
                        });
                        $(document).on("input", "#paymentAmount", function() {
                            calculation();
                        });
                        $(document).on("input", "#deliveryCharge", function() {
                            calculation();
                        });
                        $(document).on("input", "#discountCharge", function() {
                            calculation();
                        });
                        calculation();

                        function calculation() {
                            var subtotal = 0;
                            var deliveryCharge = +$("#deliveryCharge").val();
                            var discountCharge = +$("#discountCharge").val();
                            var profit = +$("#orderProfit").val() || 0;
                            var advanceDelivery = +$("#advanceDelivery").val() || 0;
                            $("#productTable tbody tr").each(function(index) {
                                subtotal = subtotal + +$(this).find(".productPrice").val() * +$(this).find(".productQuantity").val();
                            });
                            subtotal = subtotal + profit;
                            $("#subtotal").text(formatBDT(subtotal)).attr('data-raw', subtotal);
                            var totalDue = subtotal - discountCharge;
                            if (advanceDelivery == 0) {
                                totalDue = totalDue + deliveryCharge;
                            }
                            $("#total").text(formatBDT(totalDue)).attr('data-raw', totalDue);
                        }

                        $(document).on("click", ".delete-btn", function() {
                            $(this).closest("tr").remove();
                            calculation();
                        });


                    }
                });
            });

            $(".datepicker").flatpickr();

            $(document).on("click", "#sendmessage", function(e) {
                e.preventDefault();

                var customerName = $('#customerName').val();
                var customerPhone = $('#customerPhone').val();
                var invoiceID = $('#invoiceID').val();
                var orderID = $("#btn-update").val();
                var paymentTypeID = $("#paymentTypeID").select2('data');
                var paymentID = $("#paymentID").select2('data');
                var storeID = $("#storeID").val();
                if (customerName != '' && customerPhone != '' && invoiceID != '' && paymentTypeID != '' &&
                    paymentID != '') {
                    $.ajax({
                        type: "GET",
                        url: "{{ url('admin/order/sendmessage') }}",
                        data: {
                            'customerName': customerName,
                            'customerPhone': customerPhone,
                            'invoiceID': invoiceID,
                            'paymentTypeID': paymentTypeID[0].text,
                            'paymentID': paymentID[0].text,
                            'orderID': orderID,
                            'storeID': storeID,
                            '_token': token
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data['status'] === 'success') {
                                toastr.success(data["message"]);
                            } else {
                                toastr.error('Something wrong ! Please try again.');
                            }
                        }
                    });

                }


            });

            $(document).on("click", "#sendweblink", function(e) {
                e.preventDefault();

                var customerPhone = $('#customerPhone').val();
                var websiteLink = $("#websiteLink").val();
                var orderID = $("#btn-update").val();
                if (customerPhone != '' && websiteLink != '') {
                    $.ajax({
                        type: "GET",
                        url: "{{ url('admin/order/sendwebsite/link') }}",
                        data: {
                            'websiteLink': websiteLink,
                            'customerPhone': customerPhone,
                            'orderID': orderID,
                            '_token': token
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data['status'] === 'success') {
                                toastr.success(data["message"]);
                            } else {
                                toastr.error('Something wrong ! Please try again.');
                            }
                        }
                    });

                } else {
                    toastr.error('Please give website link first.');
                }


            });

        });
    </script>


    @if ($admin->hasrole('user') || $admin->hasrole('manager'))
        <style>
            .btn-delete {
                display: none;
            }
        </style>
    @else
    @endif
    <style>
        .card-box {
            background-color: #fff;
            padding: 1.5rem;
            -webkit-box-shadow: 0 1px 4px 0 rgb(0 0 0 / 10%);
            box-shadow: 0 1px 4px 0 rgb(0 0 0 / 10%);
            margin-bottom: 24px;
            border-radius: 0.25rem;
        }

        a {
            text-decoration: none;
        }
    </style>
@endsection
