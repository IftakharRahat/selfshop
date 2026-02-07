@extends('backend.master')

@section('maincontent')

@section('subcss')
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/jquery-datatables-checkboxes@1.2.13/css/dataTables.checkboxes.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
    {{-- edit payment icon --}}
    <div class="modal fade" id="editmainPrq" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-secondary rounded h-100">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: red;">Edit Product Request</h5>
                    <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form name="form" id="EditMenu" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="">Product Name</label>
                            <input type="text" name="p_name" id="p_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Message</label>
                            <input type="text" name="message" id="message" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Image</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="">Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="Pending">Pending</option>
                                <option value="Accepted">Accepted</option>
                                <option value="Done">Done</option>
                                <option value="Cancel">Cancel</option>
                            </select>
                        </div>

                        <input type="text" name="prq_id" id="prq_id" hidden>

                        <div class="m-3 ms-0 mb-0"
                            style="text-align: center;height: 100px;margin-top:20px !important">
                            <h4 style="width:30%;float: left;text-align: left;">Image : </h4>
                            <div id="previmg" style="float: left;"></div>
                        </div>
                        <br>
                        <div class="form-group mt-2" style="text-align: right">
                            <div class="submitBtnSCourse d-flex justify-content-between">
                                <button type="submit" name="btn" data-bs-dismiss="modal"
                                    class="btn btn-dark" style="float: left">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary AddCourierBtn">Update</button>
                            </div>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div><!-- End popup Modal-->

    {{-- //table section for category --}}
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body pt-4" style="text-align: center;">
                        @if (\Session::has('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-1"></i>
                                {{ \Session::get('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="buttonsec">
                            <a href="{{ url('admin/product-request/productrequestall') }}" class="btn btn-info btn-sm">Request All</a>
                            <a href="{{ url('admin/product-request/Pending') }}" class="btn btn-primary btn-sm">Pending</a>
                            <a href="{{ url('admin/product-request/Accepted') }}" class="btn btn-info btn-sm">Accepted</a>
                            <a href="{{ url('admin/product-request/Done') }}" class="btn btn-success btn-sm">Done</a>
                            <a href="{{ url('admin/product-request/Cancel') }}" class="btn btn-danger btn-sm">Cencel</a>
                        </div>
                        <!-- Table with stripped rows -->
                        <div class="table-responsive">
                            <table class="table table-centered table-borderless table-hover mb-0" id="productrqinfo"
                                width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th></th>
                                        <th>User</th>
                                        <th>Product Name</th>
                                        <th>Image</th>
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


    @if ($status)
        <input type="text" class="form-control" name="productrq_status" id="productrq_status"
            value="{{ $status }}" hidden>
    @else
        <input type="text" class="form-control" name="productrq_status" id="productrq_status" value="all" hidden>
    @endif
</div>
</div>
</div>


@section('subscript')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-datatables-checkboxes@1.2.13/js/dataTables.checkboxes.min.js"></script>
@endsection
<script>

    $(document).ready(function() {
        var statusproductrq = $('#productrq_status').val();

        var productrqinfotbl = $('#productrqinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('admin/product-request/data/') }}" + '/' + statusproductrq,
            },
            columnDefs: [{
                targets: 0,
                checkboxes: {
                    selectRow: false,
                },
            }, ],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'user'
                },
                {
                    data: 'p_name'
                },
                {
                    data: 'attachment',
                    name: 'attachment',
                    render: function(data, type, full, meta) {
                        return "<img src=../../" + data + " height=\"40\" alt='No Image'/>";
                    }
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Pending') {
                            return '<button type="button" class="btn btn-primary btn-sm" >Pending</button>';
                        }else if(data.status === 'Accepted') {
                            return '<button type="button" class="btn btn-info btn-sm" >Accepted</button>';
                        }else if(data.status === 'Done') {
                            return '<button type="button" class="btn btn-success btn-sm" >Done</button>';
                        }else{
                            return '<button type="button" class="btn btn-danger btn-sm" >Cancel</button>';
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


        //edit menu
        $(document).on('click', '#editRqBtn', function() {
            var id = $(this).attr('data-id');

            $.ajax({
                type: 'GET',
                url: "{{ url('admin/product-request') }}/" + id + "/edit",

                success: function(data) {
                    $('#EditMenu').find('#p_name').val(data.p_name);
                    $('#EditMenu').find('#message').val(data.message);
                    $('#EditMenu').find('#status').val(data.status);
                    $('#EditMenu').find('#prq_id').val(data.id);

                    $('#previmg').html('');
                    $('#previmg').append(`
                        <img  src="../../` + data.attachment + `" alt = "" style="height: 80px" />
                    `);
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
            let menuId = $('#prq_id').val();

            $.ajax({
                type: 'POST',
                url: 'update/' + menuId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditMenu').find('#p_name').val('');
                    $('#EditMenu').find('#message').val('');
                    $('#EditMenu').find('#status').val('');
                    $('#EditMenu').find('#prq_id').val('');
                    $('#previmg').html('');

                    swal({
                        title: "Product request update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    productrqinfotbl.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //delete productrq

        $(document).on('click', '#deleteComplainBtn', function() {
            let productrqsId = $(this).data('id');
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
                            url: 'productrqs/' + productrqsId,

                            success: function(data) {
                                swal("Poof! Your productrq has been deleted!", {
                                    icon: "success",
                                });
                                productrqinfotbl.ajax.reload();
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



    });

</script>

@endsection
