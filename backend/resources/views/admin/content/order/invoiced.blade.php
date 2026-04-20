@extends('backend.master')

@section('maincontent')
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
                        <li class="breadcrumb-item active">On Delivery</li>
                    </ol>
                </nav>
            </div>
        </div>

        {{-- Status pills --}}
        <style>
        .order-status-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }
        .order-status-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: #374151;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .order-status-pill:hover {
            border-color: #3b82f6;
            color: #2563eb;
            background: #eff6ff;
            text-decoration: none;
        }
        .order-status-pill.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .order-status-pill.active .pill-count {
            color: #fff;
        }
        .order-status-pill .pill-count {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }
        </style>
        <div class="order-status-bar">
            @if ($admin->hasrole('Executive'))
                <a href="{{ url('user/order') }}" class="order-status-pill {{ $status == 'orderall' ? 'active' : '' }}">
            @else
                <a href="{{ url('admin_order/orderall') }}" class="order-status-pill {{ $status == 'orderall' ? 'active' : '' }}">
            @endif
                <span class="pill-count" id="all">0</span> All
            </a>
            <a href="{{ url('admin_order/Pending') }}" class="order-status-pill {{ $status == 'Pending' ? 'active' : '' }}">
                <span class="pill-count" id="pending">0</span> Pending
            </a>
            <a href="{{ url('admin_order/Confirmed') }}" class="order-status-pill {{ $status == 'Confirmed' ? 'active' : '' }}">
                <span class="pill-count" id="confirmed">0</span> Confirmed
            </a>
            <a href="{{ url('admin_order/Processing') }}" class="order-status-pill {{ $status == 'Processing' ? 'active' : '' }}">
                <span class="pill-count" id="processing">0</span> Processing
            </a>
            <a href="{{ url('admin_order/Packageing') }}" class="order-status-pill {{ $status == 'Packageing' ? 'active' : '' }}">
                <span class="pill-count" id="packageing">0</span> Packaging
            </a>
            <a href="{{ url('admin_order/Ontheway') }}" class="order-status-pill {{ $status == 'Ontheway' ? 'active' : '' }}">
                <span class="pill-count" id="ontheway">0</span> On the Way
            </a>
            <a href="{{ url('admin_order/Delivered') }}" class="order-status-pill {{ $status == 'Delivered' ? 'active' : '' }}">
                <span class="pill-count" id="delivered">0</span> Delivered
            </a>
            <a href="{{ url('admin_order/Canceled') }}" class="order-status-pill {{ $status == 'Canceled' ? 'active' : '' }}">
                <span class="pill-count" id="canceled">0</span> Canceled
            </a>
            <a href="{{ url('admin_order/Return') }}" class="order-status-pill {{ $status == 'Return' ? 'active' : '' }}">
                <span class="pill-count" id="return">0</span> Return
            </a>
        </div>

        {{-- Edit Order Modal --}}
        <div class="modal" id="editmainOrder">
            <div class="modal-dialog" style="width: 92%;max-width: none;">
                <div class="modal-content admin-modal">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Total <span class="total">0</span> Orders</h6>
                <div class="admin-card-actions d-flex align-items-center gap-2">
                    <div class="btn-group dropdown">
                        <a href="javascript: void(0);"
                            class="btn btn-sm dropdown-toggle"
                            style="background: #fff; color: var(--admin-primary, #2d2a5d); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 6px;"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-truck me-1"></i>Assign Courier
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach (App\Models\Courier::where('status','Active')->get()->reverse() as $courier)
                                <a class="dropdown-item assign-courier" data-id="{{ $courier->id }}"
                                    href="#">{{ $courier->courierName }}</a>
                            @endforeach
                        </div>
                    </div>
                    @if ($admin->hasRole('user'))
                        <a href="{{ url('admin/create/order') }}" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                            <i class="bi bi-plus-lg me-1"></i>Create Order
                        </a>
                    @endif
                </div>
            </div>
            <div class="admin-card-body">
                @if ($message = Session::get('error'))
                    <div class="alert alert-primary alert-dismissible fade show" role="alert" style="color: red">
                        {{ $message }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (\Session::has('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ \Session::get('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="dash-toolbar">
                    <div class="dash-presets">
                        <button class="dash-preset-btn date-preset" data-preset="today">Today</button>
                        <button class="dash-preset-btn date-preset" data-preset="week">This Week</button>
                        <button class="dash-preset-btn active date-preset" data-preset="month">This Month</button>
                        <button class="dash-preset-btn date-preset" data-preset="year">This Year</button>
                        <button class="dash-preset-btn date-preset" data-preset="all">All Time</button>
                    </div>
                    <div class="dash-filter-group">
                        <label>FROM</label>
                        <input type="date" id="filter_from_date" class="form-control">
                    </div>
                    <div class="dash-filter-group">
                        <label>TO</label>
                        <input type="date" id="filter_to_date" class="form-control">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-centered table-borderless table-hover mb-0" id="orderinfo" width="100%">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Invoice ID</th>
                                <th>Name</th>
                                <th>Products</th>
                                <th>Total</th>
                                <th>Courier</th>
                                <th>Order Date</th>
                                <th>Status</th>
                                @if ($admin->hasrole('user'))
                                    <th style="width: 133px;">Notes</th>
                                @else
                                    <th style="width: 133px;">Notes</th>
                                    <th style="width: 133px;">User</th>
                                @endif
                                <th class="hidden-sm">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                @if (!$admin->hasrole('user'))
                                    <th></th>
                                @endif
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

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

<input type="hidden" name="_token" value="{{ csrf_token() }}" />

    <script>
        $(document).ready(function() {
            var orderstatus = $('#orderstatus').val();
            var user_role = $('#user_role').val();

            // Log errors to console instead of alert
            $.fn.dataTable.ext.errMode = 'none';
            console.log('[INVOICED PAGE] JS loaded. user_role=' + user_role + ', orderstatus=' + orderstatus);
            console.log('[INVOICED PAGE] AJAX URL will be: ' + "{{ url('admin/admin_order/') }}" + '/' + orderstatus);

            if (user_role == 0) {
                var orderinfotbl = $('#orderinfo').DataTable({
                    ajax: {
                        url: "{{ url('admin/admin_order/') }}" + '/' + orderstatus,
                        data: function (d) {
                            d.from_date = $('#filter_from_date').val();
                            d.to_date = $('#filter_to_date').val();
                        }
                    },
                    ordering: false,
                    processing: true,
                    serverSide: true,
                    pageLength: 30,
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
                            data: 'invoice',
                            width: "15%"
                        },
                        {
                            data: 'customerInfo',
                            width: "25%",
                            className: "customerInfo"
                        },
                        {
                            data: "products",
                            width: "15%",
                        },
                        {
                            data: "subTotal",
                            width: "5%",
                            render: function(data) { return formatBDT(data); }
                        },
                        {
                            data: "courier",
                            width: "20%",
                            searchable: false
                        },
                        {
                            data: "orderDate",
                            width: "20%"
                        },
                        {
                            data: 'statusButton',
                            width: "10%"
                        },
                        {
                            data: 'notification',
                            width: "15%"
                        },

                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },

                    ],

                    footerCallback: function() {
                        var api = this.api();
                        var numRows = api.rows().count();
                        $('.total').empty().append(numRows);

                        var intVal = function(i) {
                            return typeof i === "string" ? i.replace(/[\$,]/g, "") * 1 :
                                typeof i === "number" ? i : 0;
                        };
                        pageTotal = api.column(4, {
                            page: "current"
                        }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(4).footer()).html(formatBDT(pageTotal) + " Tk");
                    }

                });
            } else {

                var orderinfotbl = $('#orderinfo').DataTable({
                    ajax: {
                        url: "{{ url('admin/admin_order/') }}" + '/' + orderstatus,
                        data: function (d) {
                            d.from_date = $('#filter_from_date').val();
                            d.to_date = $('#filter_to_date').val();
                        }
                    },
                    ordering: false,
                    processing: true,
                    serverSide: true,
                    pageLength: 30,
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
                            data: 'invoice',
                            width: "15%"
                        },
                        {
                            data: 'customerInfo',
                            width: "25%",
                            className: "customerInfo"
                        },
                        {
                            data: "products",
                            width: "15%",
                        },
                        {
                            data: "subTotal",
                            width: "5%",
                            render: function(data) { return formatBDT(data); }
                        },
                        {
                            data: "courier",
                            width: "20%",
                            searchable: false
                        },
                        {
                            data: "orderDate",
                            width: "20%"
                        },
                        {
                            data: 'statusButton',
                            width: "10%"
                        },
                        {
                            data: 'notification',
                            width: "15%"
                        },
                        {
                            data: "user",
                            width: "5%",
                            searchable: false
                        },

                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },

                    ],

                    footerCallback: function() {
                        var api = this.api();
                        var numRows = api.rows().count();
                        $('.total').empty().append(numRows);

                        var intVal = function(i) {
                            return typeof i === "string" ? i.replace(/[\$,]/g, "") * 1 :
                                typeof i === "number" ? i : 0;
                        };
                        pageTotal = api.column(4, {
                            page: "current"
                        }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(4).footer()).html(formatBDT(pageTotal) + " Tk");
                    }

                });

            }

            $(document).on('click', '.assign-courier', function(e) {
                e.preventDefault();

                var rows_selected = orderinfotbl.column(0).checkboxes.selected();
                var ids = [];
                $.each(rows_selected, function(index, rowId) {
                    ids[index] = rowId;
                });
                var courier_id = $(this).attr('data-id');
                $('#transfer').modal('show');
                jQuery.ajax({
                    type: "get",
                    url: "{{ url('admin_order/assign_courier') }}",
                    contentType: "application/json",
                    data: {
                        action: "assign",
                        ids: ids,
                        courier_id: courier_id
                    },
                    success: function(response) {
                        $('#transfer').modal('hide');
                        var data = JSON.parse(response);
                        if (data["status"] == "success") {
                            swal({
                                title: data["message"],
                                icon: "success",
                                showCancelButton: true,
                                focusConfirm: false,
                                confirmButtonColor: "#DD6B55",
                                confirmButtonText: "Yes",
                                cancelButtonText: "No",
                            });
                            orderinfotbl.ajax.reload();
                        } else {
                            if (data["status"] == "failed") {
                                swal({
                                    title: data["message"],
                                    icon: "error",
                                    showCancelButton: true,
                                    focusConfirm: false,
                                    confirmButtonColor: "#DD6B55",
                                    confirmButtonText: "Yes",
                                    cancelButtonText: "No",
                                });
                            } else {
                                swal("Something wrong ! Please try again.");
                            }
                        }
                    }
                });

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

            //delete order selectes

            $(document).on('click', '#delete_selected_order', function(e) {
                e.preventDefault();
                var rows_selected = orderinfotbl.column(0).checkboxes.selected();
                var ids = [];
                $.each(rows_selected, function(index, rowId) {
                    ids[index] = rowId;
                });
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
                                type: "GET",
                                url: "{{ url('admin_order/delete_selected_order') }}",
                                data: {
                                    orders_id: ids,
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


                        } else {
                            swal("Your data is safe!");
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
                            ' <select type="text" class="form-control courierID" id="courierID"  onchange="courierid()"  placeholder="Courier" ></select>'
                        );
                    }
                    if (title == 'User') {
                        $(this).html(
                            ' <select type="text" style="width: 100px;" class="form-control" id="userID" placeholder="User" ></select>'
                        );
                    }
                    if (title == 'Invoice ID') {
                        $(this).html(' <input type="text" class="form-control" placeholder="User ID" />');
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

                            $('#pending').text(data["pending"]);
                            $('#canceled').text(data["canceled"]);
                            $('#confirmed').text(data["confirmed"]);
                            $('#packageing').text(data["packageing"]);
                            $('#processing').text(data["processing"]);
                            $('#ontheway').text(data["ontheway"]);
                            $('#delivered').text(data["delivered"]);
                            $('#return').text(data["return"]);

                            // console.log(data)
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


            //order sync

            $(document).on('click', '.btn-syncorder', function() {

                swal({
                    html: true,
                    title: 'Auto sync start!',
                    text: 'It will close after all Products sync.',
                    buttons: true,
                    dangerMode: true,
                    buttons: "Please Wait ...",
                });

                $.ajax({
                    type: 'GET',
                    url: 'Sync',

                    success: function(data) {
                        var datas = JSON.parse(data);
                        countorder();
                        if (datas.status == 'success') {
                            swal({
                                title: "Auto sync completed!",
                                text: datas.orders + ' order added by sync',
                                icon: "success",
                                buttons: true,
                                buttons: "Completed",
                            });
                        } else {
                            swal({
                                title: "Auto sync completed!",
                                text: 'O order added . Nothing to sync',
                                icon: "success",
                                buttons: true,
                                buttons: "Done",
                            });
                        }
                        orderinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        swal({
                            icon: 'error',
                            title: 'Cant process auto sync !',
                            text: 'Connection Error . Please wait for internet',
                            buttons: true,
                            buttons: "Thanks",
                        });
                    }

                });
            });



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

            //order edit

            $(document).on('click', '.btn-editorder', function(e) {
                e.preventDefault();
                var id = $(this).attr('data-id');
                $.ajax({
                    type: "get",
                    url: "{{ url('admin_orders') }}/" + id + "/edit",
                    success: function(response) {

                        $('#editmainOrder .modal-body').empty().append(response);
                        $('#editmainOrder').modal('toggle');
                        $('#editmainOrder .modal-footer').hide();

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
                                }
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
                                return;
                            }


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
                            $("#subtotal").text(formatBDT(subtotal)).attr('data-raw', subtotal);
                            var totalDue = subtotal + deliveryCharge - paymentAmount -
                                discountCharge;
                            $("#total").text(formatBDT(totalDue)).attr('data-raw', totalDue);
                        }

                        $(document).on("click", ".delete-btn", function() {
                            $(this).closest("tr").remove();
                            calculation();
                        });


                    }
                });
            });

            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                var id = $(this).attr('data-id');
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
                                type: 'GET',
                                url: "{{ url('admin_order') }}/" + id + "/delete",
                                data: {
                                    '_token': token
                                },
                                success: function(data) {
                                    var res = typeof data === 'string' ? JSON.parse(data) : data;
                                    if (res.status === 'success') {
                                        swal("Order has been deleted!", {
                                            icon: "success",
                                        });
                                        orderinfotbl.ajax.reload();
                                    } else {
                                        swal(res.message || "Failed to delete order.", {
                                            icon: "error",
                                        });
                                    }
                                },
                                error: function(error) {
                                    swal("Failed to delete order. Please try again.", {
                                        icon: "error",
                                    });
                                }

                            });


                        } else {
                            swal("Your data is safe!");
                        }
                    });

            });

            $(document).on("click", "#btn-update", function() {
                var id = $(this).val();
                var invoiceID = $("#invoiceID");
                var customerName = $("#customerName");
                var customerPhone = $("#customerPhone");
                var customerAddress = $("#customerAddress");
                var customerNote = $("#customerNote");
                var storeID = $("#storeID");
                var total = +$("#total").attr('data-raw') || +$("#total").text();
                var deliveryCharge = +$("#deliveryCharge").val();
                var discountCharge = +$("#discountCharge").val();
                var paymentTypeID = $("#paymentTypeID").val();
                var paymentID = $("#paymentID").val();
                var paymentAmount = +$("#paymentAmount").val();
                var paymentAgentNumber = $("#paymentAgentNumber").val();
                var orderDate = $("#orderDate");
                var courierID = $("#courierID");
                var cityID = +$("#cityID").val();
                var zoneID = +$("#zoneID").val();
                var memo = $("#memo").val();
                var product = [];
                var productCount = 0;

                $("#productTable tbody tr").each(function(index, value) {
                    var currentRow = $(this);
                    var obj = {};
                    obj.productColor = currentRow.find("#ProductColor").val();
                    obj.productSize = currentRow.find("#ProductSize").val();
                    obj.productID = currentRow.find(".productID").val();
                    obj.productCode = currentRow.find(".productCode").text();
                    obj.productName = currentRow.find(".productName").text();
                    obj.productQuantity = currentRow.find(".productQuantity").val();
                    obj.productPrice = currentRow.find(".productPrice").text();
                    product.push(obj);
                    productCount++;
                });

                if (storeID.val() == '') {
                    toastr.error('Store Should Not Be Empty');
                    storeID.closest('.form-group').find('.select2-selection').css('border',
                        '1px solid red');
                    return;
                }
                storeID.closest('.form-group').find('.select2-selection').css('border',
                    '1px solid #ced4da');

                if (invoiceID.val() == '') {
                    toastr.error('Invoice ID Should Not Be Empty');
                    invoiceID.css('border', '1px solid red');
                    return;
                }
                invoiceID.css('border', '1px solid #ced4da');

                if (customerName.val() == '') {
                    toastr.error('Customer Name Should Not Be Empty');
                    customerName.css('border', '1px solid red');
                    return;
                }
                customerName.css('border', '1px solid #ced4da');

                if (customerPhone.val() == '') {
                    toastr.error('Customer Phone Should Not Be Empty');
                    customerPhone.css('border', '1px solid red');
                    return;
                }
                customerPhone.css('border', '1px solid #ced4da');

                if (customerAddress.val() == '') {
                    toastr.error('Customer Address Should Not Be Empty');
                    customerAddress.css('border', '1px solid red');
                    return;
                }
                customerAddress.css('border', '1px solid #ced4da');

                if (orderDate.val() == '') {
                    toastr.error('Order Date Should Not Be Empty');
                    orderDate.css('border', '1px solid red');
                    return;
                }
                orderDate.css('border', '1px solid #ced4da');

                if (courierID.val() == '') {
                    toastr.error('Courier Should Not Be Empty');
                    courierID.closest('.form-group').find('.select2-selection').css('border',
                        '1px solid red');
                    return;
                }
                courierID.css('border', '1px solid #ced4da');

                if (productCount == 0) {
                    toastr.error('Product Should Not Be Empty');
                    return;
                }

                var data = {};
                data["invoiceID"] = invoiceID.val();
                data["storeID"] = storeID.val();
                data["customerName"] = customerName.val();
                data["customerPhone"] = customerPhone.val();
                data["customerAddress"] = customerAddress.val();
                data["customerNote"] = customerNote.val();
                data["total"] = total;
                data["deliveryCharge"] = deliveryCharge;
                data["discountCharge"] = discountCharge;
                data["paymentTypeID"] = paymentTypeID;
                data["paymentID"] = paymentID;
                data["paymentAmount"] = paymentAmount;
                data["paymentAgentNumber"] = paymentAgentNumber;
                data["orderDate"] = orderDate.val();
                data["courierID"] = +courierID.val();
                data["cityID"] = cityID;
                data["zoneID"] = zoneID;
                data["userID"] = $('#user_id').val();
                data["products"] = product;
                data["memo"] = memo;
                $.ajax({
                    type: "PUT",
                    url: "{{ url('admin_orders') }}/" + id,
                    data: {
                        'data': data,
                        '_token': token
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data["status"] === "success") {
                            toastr.success(data["message"]);
                            $('#editmainOrder').modal('hide');
                        } else {
                            toastr.error(data["message"]);
                        }
                        orderinfotbl.ajax.reload();
                    }
                });


            });


            $(document).on('click', '.order-print-btn', function(e) {
                e.preventDefault();
                var rows_selected = orderinfotbl.column(0).checkboxes.selected();
                var ids = [];
                $.each(rows_selected, function(index, rowId) {
                    ids[index] = rowId;
                });

                if (ids.length > 0) {

                    $.ajax({
                        type: "GET",
                        url: "{{ url('admin_order/store/Invoice') }}",
                        data: {
                            orders_id: ids
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data['status'] === 'success') {
                                window.open(data['link'], "_blank");
                                swal({
                                    title: "Are you sure?",
                                    text: "All invoiced Printed ,if ok all invoice will go to Packageing!",
                                    type: "warning",
                                    buttons: true,
                                    dangerMode: true,
                                }).then((t) => {
                                    if (t) {
                                        $.ajax({
                                            type: "get",
                                            url: "{{ url('admin_order/statusUpdateByCheckbox') }}",
                                            data: {
                                                'status': 'Packageing',
                                                'orders_id': ids,
                                                '_token': token
                                            },
                                            success: function(response) {
                                                var data = JSON.parse(
                                                    response);
                                                if (data['status'] ===
                                                    'success') {
                                                    toastr.success(data[
                                                        "message"]);
                                                    orderinfotbl.ajax
                                                        .reload();
                                                } else {
                                                    if (data['status'] ===
                                                        'failed') {
                                                        toastr.error(data[
                                                            "message"
                                                        ]);
                                                    } else {
                                                        toastr.error(
                                                            'Something wrong ! Please try again.'
                                                        );
                                                    }
                                                }
                                            }
                                        });
                                    } else {
                                        swal("Invoice Stay Pending !");
                                    }
                                });

                            } else {
                                if (data['status'] === 'failed') {
                                    toastr.error(data["message"]);
                                } else {
                                    toastr.error('Something wrong ! Please try again.');
                                }
                            }


                        }

                    });
                } else {
                    swal("Oops...!", "Select at last one", "error");
                }


            });



            $(".datepicker").flatpickr();




        });

        function courierid() {
            var cur = $('#courierID').select2('val');
            var id = $('#courier_id').val(cur);

            if (id == '') {
                swal("Oops...!", "Select at last one", "error");

            }
        }
    </script>
    <script>
        $(document).ready(function() {
            // Wait a moment for tables to initialize
            setTimeout(function() {
                var theTable = (typeof orderinfotbl !== 'undefined' && orderinfotbl !== null) ? orderinfotbl : $('#orderinfo').DataTable();

                // Set default to This Month
                var today = new Date();
                var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                
                function formatDate(date) {
                    var d = new Date(date),
                        month = '' + (d.getMonth() + 1),
                        day = '' + d.getDate(),
                        year = d.getFullYear();

                    if (month.length < 2) month = '0' + month;
                    if (day.length < 2) day = '0' + day;

                    return [year, month, day].join('-');
                }

                $('#filter_from_date').val(formatDate(firstDay));
                $('#filter_to_date').val(formatDate(today));

                // Date Picker Change Event
                $('#filter_from_date, #filter_to_date').on('change', function() {
                    $('.date-preset').removeClass('active');
                    theTable.ajax.reload();
                });

                // Preset Buttons Click Event
                $('.date-preset').on('click', function() {
                    $('.date-preset').removeClass('active');
                    $(this).addClass('active');

                    var preset = $(this).data('preset');
                    var fromDate = '';
                    var toDate = '';
                    var dt = new Date();

                    if (preset === 'today') {
                        fromDate = formatDate(dt);
                        toDate = formatDate(dt);
                    } else if (preset === 'week') {
                        var first = dt.getDate() - dt.getDay() + (dt.getDay() === 0 ? -6 : 1); // Monday
                        var firstDate = new Date(dt.setDate(first));
                        fromDate = formatDate(firstDate);
                        toDate = formatDate(new Date());
                    } else if (preset === 'month') {
                        dt = new Date();
                        fromDate = formatDate(new Date(dt.getFullYear(), dt.getMonth(), 1));
                        toDate = formatDate(new Date());
                    } else if (preset === 'year') {
                        dt = new Date();
                        fromDate = formatDate(new Date(dt.getFullYear(), 0, 1));
                        toDate = formatDate(new Date());
                    } else if (preset === 'all') {
                        fromDate = '';
                        toDate = '';
                    }

                    $('#filter_from_date').val(fromDate);
                    $('#filter_to_date').val(toDate);
                    theTable.ajax.reload();
                });
            }, 500);
        });
    </script>


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
