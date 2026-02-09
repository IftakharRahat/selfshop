@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Accounts
@endsection
<style>
    div#roleinfo_length {
        color: red;
    }

    div#roleinfo_filter {
        color: red;
    }

    div#roleinfo_info {
        color: red;
    }
</style>

<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 pb-0 rounded h-100 bg-secondary">
                <div class="d-flex align-items-center justify-content-between" style="width: 50%;float:left;">
                    <h6 class="mb-0">Payment List</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">

            <div class="p-4 rounded bg-secondary h-100">
                <div class="mb-4 row">
                    <div class="col-lg-3">
                        <div class="card card-body">
                            <p>Paid Balance</p>
                            <h4>{{$blances->sum('amount')}}</h4>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="card card-body">
                            <p>Available Balance</p>
                            <h4>{{Auth::guard()->user()->account_balance}}</h4>
                        </div>
                    </div>
                    <div class="p-4 col-12">
                        <form action="{{url('admin/accounts')}}">
                            <div class="d-flex">
                                <div class="form-group">
                                    <label for="">Invoice Id</label>
                                    <input type="text" name="search" id="search" class="form-control">
                                </div>
                                &nbsp;&nbsp;
                                <div class="mt-4">
                                    <button class="btn btn-success" type="submit">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="data-tables">
                    <table class="table table-dark" id="categoryinfo" width="100%" style="text-align: center;">
                        <thead class="thead-light">
                            <tr>
                                <th>SL</th>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($blances as $ind=>$blance)
                                <tr>
                                    <th>{{$ind+1}}</th>
                                    <th>{{$blance->order_id}}</th>
                                    <th>{{$blance->amount}}</th>
                                    <th>{{$blance->blance}}</th>
                                    <th>
                                        <button class="btn btn-info">
                                            {{$blance->status}}
                                        </button>
                                    </th>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
</div>


@endsection
