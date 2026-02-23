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
                <li class="breadcrumb-item active">Vendor Withdraws</li>
            </ol>
        </nav>
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-md-4 mb-2">
            <a href="{{url('admin/view-withdraws/Pending')}}">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center py-3">
                        <p class="mb-1" style="color: #6c757d; font-size: 13px;">Pending</p>
                        <h4 style="color: #ffc107; font-weight: 700;">{{App\Models\Vencomment::where('type', 'Withdraw')->where('status', 'Pending')->get()->count()}}</h4>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-4 mb-2">
            <a href="{{url('admin/view-withdraws/Success')}}">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center py-3">
                        <p class="mb-1" style="color: #6c757d; font-size: 13px;">Success</p>
                        <h4 style="color: #198754; font-weight: 700;">{{App\Models\Vencomment::where('type', 'Withdraw')->where('status', 'Success')->get()->count()}}</h4>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-4 mb-2">
            <a href="{{url('admin/view-withdraws/Canceled')}}">
                <div class="admin-content-card">
                    <div class="admin-card-body text-center py-3">
                        <p class="mb-1" style="color: #6c757d; font-size: 13px;">Canceled</p>
                        <h4 style="color: #dc3545; font-weight: 700;">{{App\Models\Vencomment::where('type', 'Withdraw')->where('status', 'Canceled')->get()->count()}}</h4>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Vendor Withdraws List</h6>
        </div>
        <div class="admin-card-body p-0">
            <!-- Edit Modal -->
            <div class="modal fade" id="editmainFrd" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel" style="font-weight: 600;">Edit Withdraw Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form name="form" method="POST" id="EditMenu" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Choose Payment Type</label>
                                    <select class="form-select" id="payment_type" name="payment_type" required>
                                        <option value="">Choose Payment Type</option>
                                        <option value="Bkash">Bkash</option>
                                        <option value="Nagad">Nagad</option>
                                        <option value="Rocket">Rocket</option>
                                        <option value="Bank">Bank</option>
                                    </select>
                                </div>
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" id="account_number" name="account_number" placeholder="Account Number" required>
                                    <label for="floatingInput">Account Number</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Additional Info</label>
                                    <textarea name="additional_info" class="form-control" id="additional_info" cols="30" rows="3"></textarea>
                                </div>
                                <div class="mb-3 form-floating">
                                    <input type="text" class="form-control" id="amount" name="amount" placeholder="Amount" required>
                                    <label for="floatingInput">Amount</label>
                                </div>
                                <input type="hidden" id="withdrew_id">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="Pending">Pending</option>
                                        <option value="Success">Success</option>
                                        <option value="Canceled">Canceled</option>
                                    </select>
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
                            <th>Action</th>
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
                                <td>
                                    <a href="#" type="button" id="editFrdBtn" data-id="{{$blance->id}}" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainFrd"><i class="bi bi-pencil-square"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{$blances->links('pagination::bootstrap-4')}}
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

        //edit menu
        $(document).on('click', '#editFrdBtn', function() {
            var id = $(this).attr('data-id');

            $.ajax({
                type: 'GET',
                url: "{{ url('admin/withdraw-edit') }}/" + id,

                success: function(data) {
                    $('#EditMenu').find('#payment_type').val(data.payment_type);
                    $('#EditMenu').find('#account_number').val(data.account_number);
                    $('#EditMenu').find('#additional_info').val(data.additional_info);
                    $('#EditMenu').find('#amount').val(data.amount);

                    $('#EditMenu').find('#withdrew_id').val(data.id);
                    $('#EditMenu').find('#status').val(data.status);
                    $('#EditMenu').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update menu
        $('#EditMenu').submit(function(e) {
            e.preventDefault();
            let menuId = $('#withdrew_id').val();

            $.ajax({
                type: 'POST',
                url: '../withdraw-update/' + menuId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditMenu').find('#payment_type').val('');
                    $('#EditMenu').find('#account_number').val('');
                    $('#EditMenu').find('#additional_info').val('');
                    $('#EditMenu').find('#amount').val('');
                    $('#EditMenu').find('#withdrew_id').val('');
                    $('#EditMenu').find('#status').val('');

                    swal({
                        title: "Withdrew request update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    location.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

    });
</script>

@endsection
