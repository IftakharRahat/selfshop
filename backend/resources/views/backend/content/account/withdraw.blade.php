@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Withdraws
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
                    <h6 class="mb-0">Withdraws List</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">

            <div class="p-4 rounded bg-secondary h-100">
                <div class="mb-4 row">
                    <div class="col-lg-3">
                        <div class="card card-body">
                            <p>Available Balance</p>
                            <h4>{{Auth::guard()->user()->account_balance}}</h4>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="card card-body">
                            <p>Pending Withdraw</p>
                            <h4>{{App\Models\Vencomment::where('type', 'Withdraw')->where('shop_id', Auth::guard('admin')->user()->id)->where('status','Pending')->get()->sum('amount')}}</h4>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="card card-body">
                            <p>Paid Balance</p>
                            <h4>{{App\Models\Vencomment::where('type', 'Withdraw')->where('shop_id', Auth::guard('admin')->user()->id)->where('status','Success')->get()->sum('amount')}}</h4>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            Withdraw Now
                        </button>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Make a withdraw request</h5>
                            <button type="button border-none" style="border: none" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                            <div class="modal-body">
                                <form name="form" method="POST" id="AddWithdraw" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3 form-group">
                                        <label for="">Choose Payment Types</label>
                                        <select class="form-control" name="payment_type" required>
                                            <option value="">Choose Payment Type</option>
                                            <option value="Bkash">Bkash</option>
                                            <option value="Nagad">Nagad</option>
                                            <option value="Rocket">Rocket</option>
                                            <option value="Bank">Bank</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 form-floating">
                                        <input type="text" class="form-control" name="account_number"
                                            placeholder="Account Number" required>
                                        <label for="floatingInput">Account Number</label>
                                    </div>
                                    <div class="mb-3 form-group">
                                        <label for="floatingInput">Additional Info</label>
                                        <textarea name="additional_info" class="form-control" id="additional_info" cols="30" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3 form-floating">
                                        <input type="text" class="form-control" name="amount"
                                            placeholder="Amount" required>
                                        <label for="floatingInput">Amount</label>
                                    </div>

                                    <br>
                                    <div class="mt-2 form-group" style="text-align: right">
                                        <div class="submitBtnSCourse">

                                            <button type="submit" name="btn"
                                                class="btn btn-primary AddCourierBtn btn-block">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    </div>
                <div class="data-tables">
                    <table class="table table-dark" id="categoryinfo" width="100%" style="text-align: center;">
                        <thead class="thead-light">
                            <tr>
                                <th>SL</th>
                                <th>Date</th>
                                <th>Payment Info</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($blances as $ind=>$blance)
                                <tr>
                                    <th>{{$ind+1}}</th>
                                    <th>{{$blance->created_at->format('Y-m-d')}}</th>
                                    <th>{{$blance->payment_type}}<br>{{$blance->account_number}}<br>{{$blance->additional_info}}</th>
                                    <th>{{$blance->amount}}</th>
                                    <th>{{$blance->blance}}</th>
                                    <th>
                                        <button @if($blance->status=='Success')  class="btn btn-success" @elseif($blance->status=='Canceled') class="btn btn-danger" @else class="btn btn-info" @endif>
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

<script>
    $(document).ready(function(){
        $('#AddWithdraw').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: 'withdrew-store',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {

                    if(data=='error'){
                        swal({
                            title: "Not enough balance !",
                            icon: "error",
                        });
                        productrqinfotbl.ajax.reload();
                    }else{
                        swal({
                            title: "Withdraw successfully !",
                            icon: "success",
                        });
                        productrqinfotbl.ajax.reload();
                    }

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

    });
</script>

@endsection
