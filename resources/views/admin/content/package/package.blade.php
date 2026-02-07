@extends('backend.master')

@section('maincontent')
    <div class="container-fluid pt-4 px-4">

        <div class="pagetitle row">
            <div class="col-6">
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Package</li>
                    </ol>
                </nav>
            </div>
            <div class="col-6" style="text-align: right">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#mainPackage"><span style="font-weight: bold;">+</span> Add New Package</button>
            </div>
        </div><!-- End Page Title -->

        {{-- //popup modal for create user --}}
        <div class="modal fade" id="mainPackage" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="AddPackage" enctype="multipart/form-data">
                            @csrf
                            <div class="successSMS"></div>
                            <div class="form-group mb-3">
                                <label for="websiteTitle" class="control-label">Package Name</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="package_name" id="package_name"
                                        required>
                                </div>
                            </div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Price</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="price" id="price"
                                        required>
                                </div>
                            </div>
                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Discount Price</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="discount_price" id="discount_price">
                                </div>
                            </div>
                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Validity</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="validity" id="validity">
                                </div>
                            </div>
                            <div class="form-group" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn"
                                        class="btn btn-primary AddPackageBtn btn-block">Save</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->

        {{-- //table section for category --}}

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body pt-4">
                            @if (\Session::has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-1"></i>
                                    {{ \Session::get('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif
                            <!-- Table with stripped rows -->
                            <div class="table-responsive">
                                <table class="table table-centered table-borderless table-hover mb-0" id="packageinfotbl"
                                    width="100%">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Package Name</th>
                                            <th>Amount</th>
                                            <th>Discount Price</th>
                                            <th>Validity</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <!-- End Table with stripped rows -->

                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- //popup modal for edit user --}}
        <div class="modal fade" id="editmainPackages" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="EditPackage" enctype="multipart/form-data">
                            @csrf
                            <div class="successSMS"></div>

                            <div class="form-group mb-3">
                                <label for="websiteTitle" class="control-label">Package Name</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="package_name" id="editpackage_name"
                                        required>
                                </div>
                            </div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Price</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="price" id="editprice"
                                        required>
                                </div>
                            </div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Discount Price</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="discount_price" id="editdiscount_price">
                                </div>
                            </div>

                            <div class="form-group pb-3">
                                <label for="websiteTitle" class="control-label">Validity</label>
                                <div class="webtitle">
                                    <input type="text" class="form-control" name="validity" id="editvalidity">
                                </div>
                            </div>


                            <input type="text" name="id" id="idhidden" hidden>
                            <div class="form-group" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn" class="btn btn-primary btn-block">Update</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->

    </div>


    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

    <script>
        $(document).ready(function() {
            var token = $("input[name='_token']").val();

            var packageinfotbl = $('#packageinfotbl').DataTable({
                order: [
                    [0, 'desc']
                ],
                processing: true,
                serverSide: true,
                ajax: '{!! route('package.info') !!}',
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'package_name'
                    },
                    {
                        data: 'price'
                    },
                    {
                        data: 'discount_price'
                    },
                    {
                        data: 'validity'
                    },
                    {
                        "data": null,
                        render: function(data) {

                            if (data.status === 'Active') {
                                return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="statusBtnPackage" data-id="' +
                                    data.id + '">Active</button>';
                            } else {
                                return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="statusBtnPackage" data-id="' +
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

            $('#AddPackage').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    uploadUrl: '{{ route('packages.store') }}',
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        $('#package_name').val('');
                        $('#price').val('');
                        $('#discount_price').val('');
                        $('#validity').val('');

                        swal({
                            title: "Success!",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        packageinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }
                });
            });

            //edit city

            $(document).on('click', '#editPackageBtn', function() {
                let packageId = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: 'packages/' + packageId + '/edit',

                    success: function(data) {
                        $('#EditPackage').find('#editprice').val(data.price);
                        $('#EditPackage').find('#editvalidity').val(data.validity);
                        $('#EditPackage').find('#editdiscount_price').val(data.discount_price);
                        $('#EditPackage').find('#editpackage_name').val(data.package_name);
                        $('#EditPackage').find('#idhidden').val(data.id);
                        $('#EditPackage').attr('data-id', data.id);
                    },
                    error: function(error) {
                        console.log('error');
                    }

                });
            });

            //update city
            $('#EditPackage').submit(function(e) {
                e.preventDefault();
                let packageId = $('#idhidden').val();

                $.ajax({
                    type: 'POST',
                    url: 'package/' + packageId,
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        $('#editprice').val('');
                        $('#editpackage_name').val('');
                        $('#editdiscount_price').val('');
                        $('#editvalidity').val('');


                        swal({
                            title: "Package update successfully !",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        packageinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }
                });
            });

            //deleteuser

            $(document).on('click', '#deletePackageBtn', function() {
                let packageId = $(this).data('id');
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
                                url: 'packages/' + packageId,

                                success: function(data) {
                                    swal("Poof! Your package has been deleted!", {
                                        icon: "success",
                                    });
                                    packageinfotbl.ajax.reload();
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

            $(document).on('click', '#statusBtnPackage', function() {
                let packageId = $(this).data('id');
                let packageStatus = $(this).data('status');

                $.ajax({
                    type: 'PUT',
                    url: 'package/status',
                    data: {
                        package_id: packageId,
                        status: packageStatus,
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
                        packageinfotbl.ajax.reload();
                    },
                    error: function(error) {
                        console.log('error');
                    }

                });
            });












        });
    </script>
@endsection
