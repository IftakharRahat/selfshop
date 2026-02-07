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
                    <h6 class="mb-0">Vendor Withdraws List</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">

            <div class="p-4 rounded bg-secondary h-100">
                <div class="row">
                    <div class="col-lg-3">
                        <a href="{{url('admin/view-withdraws/Pending')}}">
                            <div class="card card-body">
                                <p>Pending</p>
                                <h4>{{App\Models\Vencomment::where('type', 'Withdraw')->where('status', 'Pending')->get()->count()}}</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3">
                        <a href="{{url('admin/view-withdraws/Success')}}">
                            <div class="card card-body">
                                <p>Success</p>
                                <h4>{{App\Models\Vencomment::where('type', 'Withdraw')->where('status', 'Success')->get()->count()}}</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3">
                        <a href="{{url('admin/view-withdraws/Canceled')}}">
                            <div class="card card-body">
                                <p>Canceled</p>
                                <h4>{{App\Models\Vencomment::where('type', 'Withdraw')->where('status', 'Canceled')->get()->count()}}</h4>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="editmainFrd" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Make a withdraw request</h5>
                            <button type="button border-none" style="border: none" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                            <div class="modal-body">
                                <form name="form" method="POST" id="EditMenu" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3 form-group">
                                        <label for="">Choose Payment Types</label>
                                        <select class="form-control" id="payment_type" name="payment_type" required>
                                            <option value="">Choose Payment Type</option>
                                            <option value="Bkash">Bkash</option>
                                            <option value="Nagad">Nagad</option>
                                            <option value="Rocket">Rocket</option>
                                            <option value="Bank">Bank</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 form-floating">
                                        <input type="text" class="form-control" id="account_number" name="account_number"
                                            placeholder="Account Number" required>
                                        <label for="floatingInput">Account Number</label>
                                    </div>
                                    <div class="mb-3 form-group">
                                        <label for="floatingInput">Additional Info</label>
                                        <textarea name="additional_info" class="form-control" id="additional_info" cols="30" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3 form-floating">
                                        <input type="text" class="form-control" id="amount" name="amount"
                                            placeholder="Amount" required>
                                        <label for="floatingInput">Amount</label>
                                    </div>
                                    <input type="hidden" id="withdrew_id">
                                    <div class="form-group">
                                        <label for="">Status</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="Pending">Pending</option>
                                            <option value="Success">Success</option>
                                            <option value="Canceled">Canceled</option>
                                        </select>
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
                                <th>Blance</th>
                                <th>Status</th>
                                <th>Action</th>
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
                                    <th>
                                        <a href="#" type="button" id="editFrdBtn" data-id="{{$blance->id}}"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainFrd" ><i class="bi bi-pencil-square"></i></a>
                                    </th>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
