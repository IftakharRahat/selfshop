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

        <div class="pagetitle row">
            <div class="col-6">
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Download Info</li>
                    </ol>
                </nav>
            </div>
        </div><!-- End Page Title -->

        <div class="row">
            <form method="POST" action="{{ url('admin_order/download/excel') }}" enctype="multipart/form-data"
                style="float: left;">
                @csrf
                <div class="col-md-4" style="float: left;">
                    <div class="form-group">
                        <label for="invoiceID">Choose Status</label><br>
                        <select name="status" id="status" class="form-control">
                            <option value="">Select Status</option>
                            <option value="Processing">Processing</option>
                            <option value="Canceled">Canceled</option>
                            <option value="Completed">Completed</option>
                            <option value="Pending Invoiced"> Pending Invoiced</option>
                            <option value="Invoiced">Invoiced</option>
                            <option value="Stock Out">Stock Out</option>
                            <option value="Return">Return</option>
                            <option value="Paid">Paid</option>
                            <option value="Delivered"> Delivered</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4" style="float: left;text-align: center;padding-top: 26px;">
                    <button type="submit" class="btn btn-info download-excel-btn btn-sm">
                        <i class="fas fa-print mr-1"></i> Download Excel
                    </button>
                </div>
            </form>

        </div>

    </div>

    @if (Auth::user()->role == 0 || Auth::user()->role == 1)
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
