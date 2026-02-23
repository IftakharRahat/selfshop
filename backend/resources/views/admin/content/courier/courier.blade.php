@extends('backend.master')

@section('maincontent')
    <div class="container-fluid pt-4 px-4">

        <div class="pagetitle mb-3">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Couriers</li>
                </ol>
            </nav>
        </div>

        {{-- Add Courier Modal --}}
        <div class="modal fade" id="mainCourier" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Courier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="AddCourier" enctype="multipart/form-data">
                            @csrf
                            <div class="successSMS"></div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Courier Name</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="courierName" id="courierName" required>
                                    <span
                                        class="text-danger">{{ $errors->has('courierName') ? $errors->first('courierName') : '' }}</span>
                                </div>
                            </div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Courier Charge</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="courierCharge" id="courierCharge"
                                        required>
                                    <span
                                        class="text-danger">{{ $errors->has('courierCharge') ? $errors->first('courierCharge') : '' }}</span>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <div class="checkbox checkbox-primary mb-2">
                                    <input id="hasCity" type="checkbox" name="hasCity">
                                    <label for="hasCity">
                                        City Available
                                    </label>
                                </div>
                                <div class="checkbox checkbox-primary mb-2">
                                    <input id="hasZone" type="checkbox" name="hasZone">
                                    <label for="hasZone">
                                        Zone Available
                                    </label>
                                </div>
                            </div>
                            <div class="form-group" style="text-align: right">
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

        {{-- Courier Table --}}
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-truck me-2"></i>Courier List</h6>
                <div class="admin-card-actions">
                    <button type="button" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;" data-bs-toggle="modal"
                        data-bs-target="#mainCourier"><i class="bi bi-plus-lg me-1"></i> Add New Courier</button>
                </div>
            </div>
            <div class="admin-card-body p-0">
                @if (\Session::has('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ \Session::get('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table admin-table mb-0" id="courierinfo" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Courier Name</th>
                                <th>City Available</th>
                                <th>Zone Available</th>
                                <th>Courier Charge</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Edit Courier Modal --}}
        <div class="modal fade" id="editmainCourier" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Courier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="EditCourier" enctype="multipart/form-data">
                            @csrf
                            <div class="successSMS"></div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Courier Name</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="courierName" id="editcourierName"
                                        required>
                                    <span
                                        class="text-danger">{{ $errors->has('courierName') ? $errors->first('courierName') : '' }}</span>
                                </div>
                            </div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Courier Charge</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="courierCharge"
                                        id="editcourierCharge" required>
                                    <span
                                        class="text-danger">{{ $errors->has('courierCharge') ? $errors->first('courierCharge') : '' }}</span>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <div class="checkbox checkbox-primary mb-2">
                                    <input id="edithasCity" type="checkbox" name="hasCity">
                                    <label for="hasCity">
                                        City Available
                                    </label>
                                </div>
                                <div class="checkbox checkbox-primary mb-2">
                                    <input id="edithasZone" type="checkbox" name="hasZone">
                                    <label for="hasZone">
                                        Zone Available
                                    </label>
                                </div>
                            </div>
                            <input type="text" name="id" id="idhidden" hidden>
                            <div class="form-group" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn" class="btn btn-primary btn-block">Save</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div>

    </div>
    
    
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />


    <script>
        $(document).ready(function() {
        
         var token = $("input[name='_token']").val(); 
        
            var courierinfotbl = $('#courierinfo').DataTable({
                order: [
                    [0, 'desc']
                ],
                processing: true,
                serverSide: true,
                ajax: '{!! route('courier.info') !!}',
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'courierName'
                    },
                    {
                        data: 'hasCity'
                    },
                    {
                        data: 'hasZone'
                    },
                    {
                        data: 'courierCharge'
                    },
                    {
                        "data": null,
                        render: function(data) {

                            if (data.status === 'Active') {
                                return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="courierstatusBtn" data-id="' +
                                    data.id + '">Active</button>';
                            } else {
                                return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="courierstatusBtn" data-id="' +
                                    data.id + '" >Inactive</button>';
                            }


                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },

                ]
            });


            //add user

            $('#AddCourier').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    uploadUrl: '{{ route('couriers.store') }}',
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        $('#courierName').val('');
                        $('#courierCharge').val('');

                        swal({
                            title: "Success!",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        courierinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }
                });
            });

            //edit user

            $(document).on('click', '#editCourierBtn', function() {
                let courierId = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: 'couriers/' + courierId + '/edit',

                    success: function(data) {
                        $('#EditCourier').find('#edithasCity').prop("checked", false);
                        $('#EditCourier').find('#edithasZone').prop("checked", false);

                        $('#EditCourier').find('#editcourierName').val(data.courierName);
                        $('#EditCourier').find('#editcourierCharge').val(data.courierCharge);
                        if (data.hasCity == 'on') {
                            $('#EditCourier').find('#edithasCity').prop("checked", true);
                        }
                        if (data.hasZone == 'on') {
                            $('#EditCourier').find('#edithasZone').prop("checked", true);
                        }

                        $('#EditCourier').find('#idhidden').val(data.id);
                        $('#EditCourier').attr('data-id', data.id);
                    },
                    error: function(error) {
                        console.log('error');
                    }

                });
            });

            //update
            $('#EditCourier').submit(function(e) {
                e.preventDefault();
                let courierId = $('#idhidden').val();

                $.ajax({
                    type: 'POST',
                    url: 'courier/' + courierId,
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        $('#editcourierName').val('');
                        $('#editcourierCharge').val('');

                        swal({
                            title: "Courier update successfully !",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        courierinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }
                });
            });

            //deleteuser

            $(document).on('click', '#deleteCourierBtn', function() {
                let courierId = $(this).data('id');
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
                                type: 'DELETE',
                                url: 'couriers/' + courierId,
                                data: { 
                                    '_token': token
                                },

                                success: function(data) {
                                    swal("Poof! Your courier has been deleted!", {
                                        icon: "success",
                                    });
                                    courierinfotbl.ajax.reload();
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

            //status update

            $(document).on('click', '#courierstatusBtn', function() {
                let courierId = $(this).data('id');
                let courierStatus = $(this).data('status');

                $.ajax({
                    type: 'PUT',
                    url: 'courier/status',
                    data: {
                        courier_id: courierId,
                        status: courierStatus,
                        '_token': token
                    },

                    success: function(data) {
                        swal({
                            title: "Status updated !",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        courierinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }

                });
            });

        });
    </script>
@endsection
