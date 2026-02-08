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

    <style>
        .progress-bar {
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            background-color: #005a0a;
            transition: width 0.6s ease;
        }
        #orderinfo_filter input[type="search"]{
            display: none;
        }
    </style>
    <div class="px-4 pt-4 container-fluid">

        <div class="pagetitle row">
            <div class="col-6">
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Pending Orders</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-xl-2">
                @if ($admin->hasrole('Executive'))
                    <a href="{{ url('user/order') }}">
                    @else
                        <a href="{{ url('admin_order/orderall') }}">
                @endif
                <div class="pt-1 pb-1 widget-rounded-circle card-box order">
                    <div class="row">
                        <div class="col-12">
                            <div class="float-left">
                                <h3 class="mt-1 mb-0 text-dark">
                                    <span id="all">0</span>
                                </h3>
                                <p class="mb-1 text-muted text-truncate">All Orders</p>
                            </div>
                        </div>
                    </div> <!-- end row-->
                </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->

            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Pending') }}">
                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="pending" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Pending</p>
                                </div>
                            </div>
                        </div> <!-- end row-->
                    </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->
            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Confirmed') }}">

                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="confirmed" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Confirmed</p>
                                </div>
                            </div>
                        </div> <!-- end row-->
                    </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->
            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Processing') }}">
                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="processing" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Processing</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Packageing') }}">
                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="packageing" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Packageing</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Ontheway') }}">
                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="ontheway" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Ontheway</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Delivered') }}">
                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="delivered" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Delivered</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Canceled') }}">
                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="canceled" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Canceled</p>
                                </div>
                            </div>
                        </div> <!-- end row-->
                    </div> <!-- end widget-rounded-circle-->
                </a>
            </div> <!-- end col-->

            <div class="col-md-6 col-xl-2">
                <a href="{{ url('admin_order/Return') }}">
                    <div class="pt-1 pb-1 widget-rounded-circle card-box order" style="background: #fff;">
                        <div class="row">
                            <div class="col-12">
                                <div class="float-left">
                                    <h3 class="mt-1 mb-0 text-dark">
                                        <span id="return" data-plugin="counterup">0</span>
                                    </h3>
                                    <p class="mb-1 text-muted text-truncate">Return</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- //popup modal for edit user --}}
        <div class="modal" id="editmainOrder">
            <div class="modal-dialog" style="width: 92%;max-width: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">



                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->

        {{-- //table section for category --}}

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="pt-4 pb-2 card-body">
                            <div class="row">
                                <div class="col-4">
                                    <h4><a href="">Total <span class="total">0</span> Orders </a></h4>
                                </div>

                                <div class="col-8" style="text-align: right">
                                    <a href="{{ url('admin/create/order') }}" class="btn btn-info btn-sm"><span
                                            style="font-weight: bold;">+</span> Create New</a>
                                    <button type="button" class="btn btn-warning order-print-btn btn-sm">
                                        <i class="mr-1 fas fa-print"></i> Invoice Print
                                    </button>
                                    <div class="btn-group dropdown">
                                        <a href="javascript: void(0);" style="color: white"
                                            class="table-action-btn dropdown-toggle arrow-none btn bg-danger btn-sm"
                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                class="mr-1 fas fa-truck"></i> Assign Courier</a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @foreach (App\Models\Courier::where('status','Active')->get()->reverse() as $courier)
                                                <a class="dropdown-item assign-courier" data-id="{{ $courier->id }}"
                                                    href="#">{{ $courier->courierName }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>


                            @if (\Session::has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-1"></i>
                                    {{ \Session::get('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            @if($admin->hasrole('Shop'))
                                <!-- Table with stripped rows -->
                                <div class="table-responsive">
                                    <table class="table mb-0 table-centered table-borderless table-hover" id="orderinfo"
                                        width="100%">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Invoice ID</th>
                                                <th>Name</th>
                                                <th>Products</th>
                                                <th>Wholesale Price</th>
                                                <th>Total</th>
                                                <th>Courier</th>
                                                <th>Order Date</th>
                                                <th>Status</th>
                                                    <th style="width: 133px;">User</th>

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
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <!-- Table with stripped rows -->
                                <div class="table-responsive">
                                    <table class="table mb-0 table-centered table-borderless table-hover" id="orderinfo"
                                        width="100%">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Invoice ID</th>
                                                <th>Name</th>
                                                <th>Products</th>
                                                <th>RS Price</th>
                                                <th>Profit</th>
                                                <th>Total</th>
                                                <th>Courier</th>
                                                <th>Order Date</th>
                                                <th>Status</th>
                                                    <th style="width: 133px;">User</th>

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
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif


                            <!-- End Table with stripped rows -->

                        </div>
                    </div>

                </div>
            </div>
        </section>
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
        {{-- //user role --}}
        @if ($admin->hasrole('Executive'))
            <input type="text" id="user_role" value="0" hidden>
        @else
            <input type="text" id="user_role" value="1" hidden>
        @endif

        @if (empty($status))
        @else
            <input type="text" id="orderstatus" value="{{ $status }}" hidden>
        @endif
    </div>


    <div class="modal" id="froudcheck">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="display:inline !important">
                    <div class="d-flex justify-content-between">
                        <h5 class="modal-title">INV#<span id="invnum"></span></h5>
                        <button type="button" class="close" id="discol" data-bs-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <h5 class="modal-title" id="mtitle"> Parcel Receive & Cancel Ratio of :- <b><span style="color:red" id="cusnum"></span></b> </h5>
                </div>
                <div class="modal-body" style="text-align: center;">
                    <div class="text-center auto-load" style="display: none;">
                        <svg version="1.1" id="L9" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                            x="0px" y="0px" height="60" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
                            <path fill="#000"
                                d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50">
                                <animateTransform attributeName="transform" attributeType="XML" type="rotate" dur="1s"
                                    from="0 50 50" to="360 50 50" repeatCount="indefinite" />
                            </path>
                        </svg>
                    </div>
                    <div  id="cuslist">

                    </div>
                </div>

            </div>
        </div>
    </div><!-- End popup Modal-->

    @if($admin->hasrole('Shop'))
        <script>
            $(document).ready(function() {
                var token = $("input[name='_token']").val();

                var orderstatus = $('#orderstatus').val();
                var user_role = $('#user_role').val();

                var orderinfotbl = $('#orderinfo').DataTable({
                    ajax: {
                        url: "{{ url('admin/admin_order/') }}" + '/' + orderstatus,
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
                            data: "wholesale",
                            width: "5%"
                        },
                        {
                            data: "subTotal",
                            width: "5%"
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
                        $(api.column(4).footer()).html(pageTotal + " Tk");
                        pageTotal = api.column(5, {
                            page: "current"
                        }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(5).footer()).html(pageTotal + " Tk");
                        pageTotal = api.column(6, {
                            page: "current"
                        }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(6).footer()).html(pageTotal + " Tk");
                    }

                });

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
                                title:data["message"],
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
                                    title:data["message"],
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
                                    countorder();
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
                            $('#packageing').text(data["packageing"]);
                            $('#processing').text(data["processing"]);
                            $('#ontheway').text(data["ontheway"]);
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

            //order edit

           $(document).on('click', '#checkfraud', function(e) {
                e.preventDefault();
                var number = $(this).attr('data-num');
                var inv = $(this).attr('data-inv');
                $('#froudcheck').modal('show');
                $('#cuslist').empty();
                $('.auto-load').css('display','inline');
                $('#cusnum').html(number);
                $('#invnum').html(inv);
                $.ajax({
                    type: "GET",
                    url: "{{ url('admin/fraud-check-data') }}",
                    data: {
                        'number': number,
                        '_token': token
                    },
                    success: function(response) {
                        $('.auto-load').css('display','none');
                        $('#cuslist').empty().append(response);
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
                        $('#editmainOrder .modal-body').html('');
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
                                '<td><input type="number" id="productPrice" class="form-control" style="width:80px;" value="'+ e.params.data
                                .productPrice +'"></td>' +
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
                                        return '<i class="mr-2 fas fa-user text-grey-dark"></i>' +
                                            data.customerName +
                                            '<br> <i class="mr-2 fas fa-phone text-grey-dark"></i>' +
                                            data.customerPhone +
                                            '<br><i class="mr-2 fas fa-map-marker text-grey-dark"></i>' +
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
                        $(document).on("input", "#productPrice", function() {
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
                                subtotal = subtotal + +$(this).find("#productPrice").val() * +$(this).find(".productQuantity").val();
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
                                    swal("Category has been deleted!", {
                                        icon: "success",
                                    });
                                    orderinfotbl.ajax.reload();
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



            $(document).on("click", "#btn-update", function() {
                var id = $(this).val();
                var invoiceID = $("#invoiceID");
                var trackingLink = $("#trackingLink");
                var customerName = $("#customerName");
                var customerPhone = $("#customerPhone");
                var customerAddress = $("#customerAddress");
                var customerNote = $("#customerNote");
                var cancel_comment = $("#cancel_comment");
                var storeID = $("#storeID");
                var total = +$("#total").text();
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
                    obj.productPrice = currentRow.find("#productPrice").val();
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
                data["trackingLink"] = trackingLink.val();
                data["storeID"] = storeID.val();
                data["customerName"] = customerName.val();
                data["customerPhone"] = customerPhone.val();
                data["customerAddress"] = customerAddress.val();
                data["customerNote"] = customerNote.val();
                data["cancel_comment"] = cancel_comment.val();
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
                            $('#editmainOrder').modal('toggle');

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
                                    text: "All invoiced Printed. If ok it will go to Packageing !",
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
    @else
        <script>
            $(document).ready(function() {
                var token = $("input[name='_token']").val();

                var orderstatus = $('#orderstatus').val();
                var user_role = $('#user_role').val();

                var orderinfotbl = $('#orderinfo').DataTable({
                    ajax: {
                        url: "{{ url('admin/admin_order/') }}" + '/' + orderstatus,
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
                            data: "resellprice",
                            width: "5%"
                        },
                        {
                            data: "profit",
                            width: "5%"
                        },
                        {
                            data: "subTotal",
                            width: "5%"
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
                        $(api.column(4).footer()).html(pageTotal + " Tk");
                        pageTotal = api.column(5, {
                            page: "current"
                        }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(5).footer()).html(pageTotal + " Tk");
                        pageTotal = api.column(6, {
                            page: "current"
                        }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(6).footer()).html(pageTotal + " Tk");
                    }

                });

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
                                title:data["message"],
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
                                    title:data["message"],
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
                                    countorder();
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
                            $('#packageing').text(data["packageing"]);
                            $('#processing').text(data["processing"]);
                            $('#ontheway').text(data["ontheway"]);
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

            //order edit

           $(document).on('click', '#checkfraud', function(e) {
                e.preventDefault();
                var number = $(this).attr('data-num');
                var inv = $(this).attr('data-inv');
                $('#froudcheck').modal('show');
                $('#cuslist').empty();
                $('.auto-load').css('display','inline');
                $('#cusnum').html(number);
                $('#invnum').html(inv);
                $.ajax({
                    type: "GET",
                    url: "{{ url('admin/fraud-check-data') }}",
                    data: {
                        'number': number,
                        '_token': token
                    },
                    success: function(response) {
                        $('.auto-load').css('display','none');
                        $('#cuslist').empty().append(response);
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
                        $('#editmainOrder .modal-body').html('');
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
                                '<td><input type="number" id="productPrice" class="form-control" style="width:80px;" value="'+ e.params.data
                                .productPrice +'"></td>' +
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
                                        return '<i class="mr-2 fas fa-user text-grey-dark"></i>' +
                                            data.customerName +
                                            '<br> <i class="mr-2 fas fa-phone text-grey-dark"></i>' +
                                            data.customerPhone +
                                            '<br><i class="mr-2 fas fa-map-marker text-grey-dark"></i>' +
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
                        $(document).on("input", "#productPrice", function() {
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
                                subtotal = subtotal + +$(this).find("#productPrice").val() * +$(this).find(".productQuantity").val();
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
                                    swal("Category has been deleted!", {
                                        icon: "success",
                                    });
                                    orderinfotbl.ajax.reload();
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



            $(document).on("click", "#btn-update", function() {
                var id = $(this).val();
                var invoiceID = $("#invoiceID");
                var trackingLink = $("#trackingLink");
                var customerName = $("#customerName");
                var customerPhone = $("#customerPhone");
                var customerAddress = $("#customerAddress");
                var customerNote = $("#customerNote");
                var cancel_comment = $("#cancel_comment");
                var storeID = $("#storeID");
                var total = +$("#total").text();
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
                    obj.productPrice = currentRow.find("#productPrice").val();
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
                data["trackingLink"] = trackingLink.val();
                data["storeID"] = storeID.val();
                data["customerName"] = customerName.val();
                data["customerPhone"] = customerPhone.val();
                data["customerAddress"] = customerAddress.val();
                data["customerNote"] = customerNote.val();
                data["cancel_comment"] = cancel_comment.val();
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
                            $('#editmainOrder').modal('toggle');

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
                                    text: "All invoiced Printed. If ok it will go to Packageing !",
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
    @endif



    @if ($admin->hasrole('Executive') || $admin->hasrole('manager'))
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
