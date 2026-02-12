@extends('backend.master')

@section('maincontent')

<style>
    .process-steps {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .process-steps li {
        width: 11%;
        float: left;
        text-align: center;
        position: relative;
    }

    .process-steps li .icon {
        height: 30px;
        width: 30px;
        margin: auto;
        background: #fff;
        border-radius: 50%;
        line-height: 30px;
        font-size: 14px;
        font-weight: 700;
        color: #adadad;
        position: relative;
    }

    .process-steps li .title {
        font-weight: 600;
        font-size: 13px;
        color: #777;
        margin-top: 8px;
        margin-bottom: 0;
    }

    .process-steps li+li:after {
        position: absolute;
        content: "";
        height: 3px;
        width: calc(100% - 30px);
        background: #fff;
        top: 14px;
        z-index: 0;
        right: calc(50% + 15px);
    }

    .breadcrumb {
        padding: 5px 0;
        border-bottom: 1px solid #e9e9e9;
        background-color: #fafafa;
    }

    .search-area .search-button {
        border-radius: 0px 3px 3px 0px;
        display: inline-block;
        float: left;
        margin: 0px;
        padding: 5px 15px 6px;
        text-align: center;
        background-color: #e62e04;
        border: 1px solid #e62e04;
    }

    .search-area .search-button:after {
        color: #fff;
        content: "\f002";
        font-family: fontawesome;
        font-size: 16px;
        line-height: 9px;
        vertical-align: middle;
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

        <div class="pagetitle row">
            <div class="col-6">
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">View Orders</li>
                    </ol>
                </nav>
            </div>
        </div>


        {{-- //table section for category --}}

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body pt-4 pb-2">
                            @if (isset($orders))
                                {{-- track list --}}
                                <div class="card mt-4">
                                    <div class="card-header py-2 px-3 heading-6 strong-600 clearfix">
                                        <div class="float-center" style="color: red;text-align:center"> <b>INVOICE OF {{ $orders->invoiceID }}</b> </div>
                                        <div class="card-header py-2 px-3 heading-6 strong-600 clearfix">
                                            <ul class="process-steps clearfix">

                                                @if ($orders->status == 'Pending')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">1</div>
                                                        <div class="title" style="color:red">Pending</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">1</div>
                                                        <div class="title">Pending</div>
                                                    </li>
                                                @endif
                                                @if ($orders->status == 'Confirmed')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">2</div>
                                                        <div class="title" style="color:red">Confirmed</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">2</div>
                                                        <div class="title">Confirmed</div>
                                                    </li>
                                                @endif
                                                @if ($orders->status == 'Processing')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">3</div>
                                                        <div class="title" style="color:red">Processing</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">3</div>
                                                        <div class="title">Processing</div>
                                                    </li>
                                                @endif

                                                @if ($orders->status == 'Packageing')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">4</div>
                                                        <div class="title" style="color:red">Packageing</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">4</div>
                                                        <div class="title">Packageing</div>
                                                    </li>
                                                @endif


                                                @if ($orders->status == 'Ontheway')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">5</div>
                                                        <div class="title" style="color:red">On the way</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">5</div>
                                                        <div class="title">On the way</div>
                                                    </li>
                                                @endif

                                                @if ($orders->status == 'Delivered')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">6</div>
                                                        <div class="title" style="color:red">Delivered</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">6</div>
                                                        <div class="title">Delivered</div>
                                                    </li>
                                                @endif
                                                @if ($orders->status == 'Canceled')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">7</div>
                                                        <div class="title" style="color:red">Canceled</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">7</div>
                                                        <div class="title">Canceled</div>
                                                    </li>
                                                @endif

                                                @if ($orders->status == 'Return')
                                                    <li>
                                                        <div class="icon" style="background:#e62e04;color:white">8</div>
                                                        <div class="title" style="color:red">Return</div>
                                                    </li>
                                                @else
                                                    <li>
                                                        <div class="icon">8</div>
                                                        <div class="title">Return</div>
                                                    </li>
                                                @endif

                                            </ul>
                                        </div>
                                    </div>

                                    <div class="card-body pb-0">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <table class="details-table table">
                                                    <tbody>
                                                        <tr>
                                                            <td class="w-50 strong-600">Order ID:</td>
                                                            <td>{{ $orders->invoiceID }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="w-50 strong-600">Customer:</td>
                                                            <td>{{ $orders->customers?->customerName ?? '—' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="w-50 strong-600">Phone:</td>
                                                            <td>{{ $orders->customers?->customerPhone ?? '—' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="w-50 strong-600">Shipping address:</td>
                                                            <td>{{ $orders->customers?->customerAddress ?? '—' }},@if (isset($orders->zones))
                                                                    {{ $orders->zones->zoneName }},
                                                                @else
                                                                    @endif @if (isset($orders->cities))
                                                                        {{ $orders->cities->cityName }},
                                                                    @else
                                                                    @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="w-50 strong-600">Shipping company:</td>
                                                            <td>
                                                                @if (isset($orders->couriers))
                                                                    {{ $orders->couriers->courierName }}
                                                                @else
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-lg-6">
                                                <table class="details-table table">
                                                    <tbody>
                                                        <tr>
                                                            <td class="w-50 strong-600">Order date:</td>
                                                            <td>
                                                                @if($orders->created_at)
                                                                    {{ $orders->created_at->format('Y-m-d') }} , {{ $orders->created_at->format('h:i A') }}
                                                                @else
                                                                    —
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="w-50 strong-600">Total order amount:</td>
                                                            <td>৳ {{ (float)($orders->subTotal ?? 0) + (float)($orders->paymentAmount ?? 0) - (float)($orders->deliveryCharge ?? 0) }} + <span style="color: red">( Charge : {{ $orders->deliveryCharge ?? 0 }} ৳)</span> </td>
                                                        </tr>

                                                        <tr>
                                                            <td class="w-50 strong-600">Payment method:</td>
                                                            <td>
                                                                @if ($orders->Payment == 'C-O-D')
                                                                    Cash On Delivery
                                                                @else
                                                                    Online Payment
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="w-50 strong-600">Paid:</td>
                                                            <td>
                                                                @if($orders->paymentAmount>0)
                                                                    {{$orders->paymentAmount}} TK
                                                                @else
                                                                    00 TK
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="w-50 strong-600">Due:</td>
                                                            <td>
                                                                {{$orders->subTotal}} TK
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mt-4">

                                    <div class="card-body p-4">
                                        <div class="col-12">
                                            <table class="details-table table">
                                                <tbody>
                                                    @forelse ($orders->orderproducts as $products)
                                                        <tr>
                                                            <td class="w-50 strong-600">Product Name:</td>
                                                            <td>{{ $products->productName }} &nbsp; <span style="color: red">(
                                                                    {{ $products->quantity }}
                                                                    pics )</span>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                    @endforelse
                                                    <tr>
                                                        <td class="w-50 strong-600">Completed By:</td>
                                                        <td>{{ $orders->admins?->name ?? '—' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            @else

                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </section>
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
                                '<td><span class="productPrice">' + e.params.data
                                .productPrice + '</span></td>' +
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
                            var paymentAmount = +$("#paymentAmount").val();
                            $("#productTable tbody tr").each(function(index) {
                                subtotal = subtotal + +$(this).find(".productPrice")
                                    .text() * +$(this).find(".productQuantity").val();
                            });
                            $("#subtotal").text(subtotal);
                            $("#total").text(subtotal + deliveryCharge - paymentAmount -
                                discountCharge);
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
