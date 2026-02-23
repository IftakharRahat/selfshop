@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Withdraws
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Withdraws</li>
            </ol>
        </nav>
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center py-3">
                    <p class="mb-1" style="color: #6c757d; font-size: 13px;">Available Balance</p>
                    <h4 style="color: var(--admin-primary, #2d2a5d); font-weight: 700;">{{Auth::guard()->user()->account_balance}}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center py-3">
                    <p class="mb-1" style="color: #6c757d; font-size: 13px;">Pending Withdraw</p>
                    <h4 style="color: #ffc107; font-weight: 700;">{{App\Models\Vencomment::where('type', 'Withdraw')->where('shop_id', Auth::guard('admin')->user()->id)->where('status','Pending')->get()->sum('amount')}}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center py-3">
                    <p class="mb-1" style="color: #6c757d; font-size: 13px;">Paid Balance</p>
                    <h4 style="color: #198754; font-weight: 700;">{{App\Models\Vencomment::where('type', 'Withdraw')->where('shop_id', Auth::guard('admin')->user()->id)->where('status','Success')->get()->sum('amount')}}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-2 d-flex align-items-center">
            <button type="button" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="bi bi-wallet2 me-1"></i> Withdraw Now
            </button>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Withdraws List</h6>
        </div>
        <div class="admin-card-body p-0">
            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel" style="font-weight: 600;">Make a withdraw request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form name="form" method="POST" id="AddWithdraw" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Choose Payment Type</label>
                                    <select class="form-select" name="payment_type" required>
                                        <option value="">Choose Payment Type</option>
                                        <option value="Bkash">Bkash</option>
                                        <option value="Nagad">Nagad</option>
                                        <option value="Rocket">Rocket</option>
                                        <option value="Bank">Bank</option>
                                    </select>
                                </div>
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" name="account_number" placeholder="Account Number" required>
                                    <label for="floatingInput">Account Number</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Additional Info</label>
                                    <textarea name="additional_info" class="form-control" id="additional_info" cols="30" rows="3"></textarea>
                                </div>
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" name="amount" placeholder="Amount" required>
                                    <label for="floatingInput">Amount</label>
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" name="btn" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table admin-table mb-0" id="categoryinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Date</th>
                            <th>Payment Info</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($blances as $ind=>$blance)
                            <tr>
                                <td>{{$ind+1}}</td>
                                <td>{{$blance->created_at->format('Y-m-d')}}</td>
                                <td>{{$blance->payment_type}}<br>{{$blance->account_number}}<br>{{$blance->additional_info}}</td>
                                <td>{{$blance->amount}}</td>
                                <td>{{$blance->blance}}</td>
                                <td>
                                    @if($blance->status=='Success')
                                        <span class="badge bg-success">{{$blance->status}}</span>
                                    @elseif($blance->status=='Canceled')
                                        <span class="badge bg-danger">{{$blance->status}}</span>
                                    @else
                                        <span class="badge bg-info">{{$blance->status}}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
