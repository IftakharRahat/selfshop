@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Accounts
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Payments</li>
            </ol>
        </nav>
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center py-3">
                    <p class="mb-1" style="color: #6c757d; font-size: 13px;">Paid Balance</p>
                    <h4 style="color: var(--admin-primary, #2d2a5d); font-weight: 700;">{{$blances->sum('amount')}}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center py-3">
                    <p class="mb-1" style="color: #6c757d; font-size: 13px;">Available Balance</p>
                    <h4 style="color: var(--admin-primary, #2d2a5d); font-weight: 700;">{{Auth::guard()->user()->account_balance}}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Payment List</h6>
            <div class="admin-card-actions">
                <form action="{{url('admin/accounts')}}" class="d-flex gap-2 align-items-end">
                    <div>
                        <label class="form-label mb-0" style="font-size: 12px;">Invoice Id</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm">
                    </div>
                    <button class="btn btn-sm btn-success">Search</button>
                </form>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="categoryinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($blances as $ind=>$blance)
                            <tr>
                                <td>{{$ind+1}}</td>
                                <td>{{$blance->order_id}}</td>
                                <td>{{$blance->amount}}</td>
                                <td>{{$blance->blance}}</td>
                                <td>
                                    <span class="badge bg-info">{{$blance->status}}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
