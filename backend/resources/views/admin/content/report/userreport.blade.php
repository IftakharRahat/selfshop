@extends('backend.master')

@section('maincontent')
    <div class="px-4 pt-4 container-fluid">
        <div class="pagetitle mb-3">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{url('/admindashboard')}}">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">User Report</li>
                </ol>
            </nav>
        </div>

        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">User Report</h6>
                <div class="admin-card-actions">
                    <button class="btn btn-sm btn-outline-primary btn-print-courieruserreport"><i class="bi bi-printer me-1"></i> Print</button>
                </div>
            </div>
            <div class="admin-card-body">
                {{-- Date preset toolbar --}}
                <div class="dash-toolbar mb-3">
                    <div class="dash-presets" id="reportPresets">
                        <button class="dash-preset-btn active" data-range="today" onclick="reportDatePreset('today',this)">Today</button>
                        <button class="dash-preset-btn" data-range="week" onclick="reportDatePreset('week',this)">This Week</button>
                        <button class="dash-preset-btn" data-range="month" onclick="reportDatePreset('month',this)">This Month</button>
                        <button class="dash-preset-btn" data-range="year" onclick="reportDatePreset('year',this)">This Year</button>
                        <button class="dash-preset-btn" data-range="all" onclick="reportDatePreset('all',this)">All Time</button>
                    </div>
                    <div class="dash-filter-group">
                        <label>From</label>
                        <input type="date" class="form-control datepicker" id="startDate" value="<?php echo date('Y-m-d')?>" placeholder="Select Date">
                    </div>
                    <div class="dash-filter-group">
                        <label>To</label>
                        <input type="date" class="form-control datepicker" id="endDate" value="<?php echo date('Y-m-d')?>" placeholder="Select Date">
                    </div>
                </div>
                {{-- Additional filters --}}
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Select User</label>
                        <select id="userID" class="form-select"></select>
                    </div>
                </div>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table id="webreportTable" class="table admin-table mb-0" style="width: 100%">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>User Name</th>
                            <th>Total Order</th>
                            <th>Pending</th>
                            <th>Canceled</th>
                            <th>Confirmed</th>
                            <th>Invoiced</th>
                            <th>On Delivery</th>
                            <th>Delivered</th>
                            <th>Return</th>
                            <th>Paid Amount</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                        <tr>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function reportDatePreset(range, btn) {
            var today = new Date();
            var start, end;
            end = today.toISOString().slice(0, 10);
            switch (range) {
                case 'today': start = end; break;
                case 'week': var d = new Date(today); d.setDate(d.getDate() - d.getDay()); start = d.toISOString().slice(0, 10); break;
                case 'month': start = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-01'; break;
                case 'year': start = today.getFullYear() + '-01-01'; break;
                case 'all': start = '2020-01-01'; break;
                default: start = end;
            }
            $('#startDate').val(start);
            $('#endDate').val(end);
            if ($('#startDate')[0]._flatpickr) $('#startDate')[0]._flatpickr.setDate(start, false);
            if ($('#endDate')[0]._flatpickr) $('#endDate')[0]._flatpickr.setDate(end, false);
            $('#reportPresets .dash-preset-btn').removeClass('active');
            if (btn) $(btn).addClass('active');
            if (typeof table !== 'undefined') table.ajax.reload();
        }

        $(document).ready(function() {
            var token = $("input[name='_token']").val();
            $(".datepicker").flatpickr();

            var table = $("#webreportTable").DataTable({
                type: "get",
                ajax: {
                    url: "{{url('admin/user/report/data')}}",
                    data: {
                        startDate: function() { return $('#startDate').val() },
                        endDate: function() { return $('#endDate').val() },
                        userID: function() { return $('#userID').val() }
                    }
                },
                ordering: false,
                paging: false,
                lengthChange: false,
                bFilter:false,
                search:false,
                info:false,
                dom: '<"row"<"col-sm-6"Bl><"col-sm-6"f>>' +
                    '<"row"<"col-sm-12"<"table-responsive"tr>>>' +
                    '<"row"<"col-sm-5"i><"col-sm-7"p>>',
                buttons: {
                    buttons: [{
                        extend: 'print',
                        text: 'Print',
                        footer: true,
                        title: function(){
                            return 'User Report';
                        },
                        customize: function (win) {
                            $(win.document.body).find('h1').css('text-align','center');
                            $(win.document.body).find('h1').after('<p style="text-align: center">From : '+$('#startDate').val()+' - To : '+$('#endDate').val()+'</p>');

                        }
                    }]
                },
                columns: [
                    {data: "date"},
                    {data: "name"},
                    {data: "all"},
                    {data: "pending"},
                    {data: "canceled"},
                    {data: "confirmed"},
                    {data: "invoiced"},
                    {data: "ondelivery"},
                    {data: "delivered"},
                    {data: "return"},
                    {data: "paidAmount"}
                ],
                language: {
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'>",
                        next: "<i class='fas fa-chevron-right'>"
                    }
                },
                drawCallback: function () {
                    $(".dataTables_paginate > .pagination").addClass("pagination-sm");
                    $('.dt-buttons').hide();
                },
                footerCallback: function ( ) {
                    var api = this.api();
                    var numRows = api.rows().count();
                    $('.total').empty().append(numRows);

                    var intVal = function (i) {
                        return typeof i === "string" ? i.replace(/[\$,]/g, "") * 1 : typeof i === "number" ? i : 0;
                    };
                    for(var i = 2; i<=9;i++){
                        OrderTotal = api.column(i, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(i).footer()).html(OrderTotal);
                    }
                    Total = api.column(10, { page: "current" }).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                    $(api.column(10).footer()).html(Total + " Tk");
                }

            });

            $("#userID").select2({
                placeholder: "Select a User",
                allowClear:true,
                ajax: {
                    url:'{{url('admin_order/users')}}',
                    processResults: function (data) {
                        var data = $.parseJSON(data);
                        return {
                            results: data
                        };
                    }
                }
            }).trigger("change").on("select2:select", function (e) {
                table.ajax.reload();
            });

            $(document).on('click', '.btn-print-courieruserreport', function(){
                $(".buttons-print")[0].click();
            });
            $(document).on('change', '#startDate', function(){
                table.ajax.reload();
            });
            $(document).on('change', '#endDate', function(){
                table.ajax.reload();
            });
            $(document).on('change', '#orderStatus', function(){
                table.ajax.reload();
            });


        });

    </script>

<style>
.select2-container--default .select2-selection--single {
    display: block;
    width: 100%;
    height: calc(1.5em + 0.9rem + 2px);
    padding: 0.3rem 0.9rem;
    font-size: .875rem;
    font-weight: 400;
    line-height: 1.5;
    color: #383b3d;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.2rem;
}
span.select2-selection__arrow {
    margin-top: 5px;
}
</style>



@endsection
