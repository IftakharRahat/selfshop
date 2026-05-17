@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Slider
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Slider List</h6>
                    <div class="admin-card-actions">
                        @php $adm = Auth::guard('admin')->user(); @endphp
                        @if($adm->isFullAdmin() || $adm->hasDirectPermission('banner.create'))
                        <a type="button" data-bs-toggle="modal" data-bs-target="#mainSlider" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Create Slider
                        </a>
                        @endif
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table" id="sliderinfo" width="100%">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- create slider modal --}}
        <div class="modal fade" id="mainSlider" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Create New Slider</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="AddSlider" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="slider_title" id="slider_title"
                                    placeholder="Enter slider title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Small Title</label>
                                <input type="text" class="form-control" name="slider_small_title"
                                    id="slider_small_title" placeholder="Enter small title">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Text</label>
                                <textarea class="form-control" placeholder="Enter slider text" name="slider_text" id="slider_text" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Button Name</label>
                                <input type="text" class="form-control" name="slider_btn_name" id="slider_btn_name"
                                    placeholder="Enter button name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="slider_btn_link" id="slider_btn_link"
                                    placeholder="Enter button link">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slider Image</label>
                                <input class="form-control" name="slider_image"
                                    id="slider_image" type="file">
                            </div>
                            <div class="admin-modal-footer">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-outline-secondary btn-sm">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary AddCourierBtn btn-sm">Save</button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End create modal-->

        {{-- edit slider modal --}}
        <div class="modal fade" id="editmainSlider" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Edit Slider</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">

                        <form name="form" id="EditSlider" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="slider_title" id="slider_title"
                                    placeholder="Enter slider title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Small Title</label>
                                <input type="text" class="form-control" name="slider_small_title"
                                    id="slider_small_title" placeholder="Enter small title">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Text</label>
                                <textarea class="form-control" placeholder="Enter slider text" name="slider_text" id="slider_text" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Button Name</label>
                                <input type="text" class="form-control" name="slider_btn_name"
                                    id="slider_btn_name" placeholder="Enter button name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="slider_btn_link"
                                    id="slider_btn_link" placeholder="Enter button link">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Slider Image</label>
                                <input class="form-control" name="slider_image"
                                    id="slider_image" type="file">
                            </div>
                            <input type="text" name="slider_id" id="slider_id" hidden>
                            <div class="mb-3 d-flex align-items-center gap-3">
                                <span class="form-label mb-0">Current Image:</span>
                                <div id="previmg"></div>
                            </div>
                            <div class="admin-modal-footer">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-outline-secondary btn-sm">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary AddCourierBtn btn-sm">Update</button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End edit modal-->
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        var sliderinfo = $('#sliderinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.slider.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'slider_image',
                    name: 'slider_image',
                    orderable: false,
                    render: function(data, type, full, meta) {
                        if (data && data.trim() !== '') {
                            var imgSrc = data.startsWith('http') ? data : '../' + data;
                            return '<img src="' + imgSrc + '" height="40" style="border-radius:6px;object-fit:cover;width:60px;height:40px;" onerror="this.outerHTML=\'<div style=padding:8px;width:60px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px><i class=bi.bi-image></i></div>\'" />';
                        } else {
                            return '<div style="width:60px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px"><i class="bi bi-image"></i></div>';
                        }
                    }
                },
                {
                    data: 'slider_title'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="sliderstatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="sliderstatusBtn" data-id="' +
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


        //add slider

        $('#AddSlider').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.sliders.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    if (data == 'error') {
                        swal({
                            icon: 'error',
                            title: 'Can not save Slider',
                            text: 'Please fill Title Name',
                            buttons: true,
                            buttons: "Thanks",
                        });
                    } else {
                        $('#slider_small_title').val('');
                        $('#slider_title').val('');
                        $('#slider_text').val('');
                        $('#slider_btn_name').val('');
                        $('#slider_btn_link').val('');
                        $('#slider_image').val('');

                        swal({
                            title: "Success!",
                            icon: "success",
                        });
                        sliderinfo.ajax.reload();
                    }

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit slider
        $(document).on('click', '#editSliderBtn', function() {
            let sliderId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'sliders/' + sliderId + '/edit',

                success: function(data) {
                    $('#EditSlider').find('#slider_small_title').val(data
                        .slider_small_title);
                    $('#EditSlider').find('#slider_title').val(data.slider_title);
                    $('#EditSlider').find('#slider_text').val(data.slider_text);
                    $('#EditSlider').find('#slider_btn_name').val(data.slider_btn_name);
                    $('#EditSlider').find('#slider_btn_link').val(data.slider_btn_link);

                    $('#EditSlider').find('#slider_id').val(data.id);

                    $('#previmg').html('');
                    var prevSrc = data.slider_image && data.slider_image.startsWith('http') ? data.slider_image : '../' + data.slider_image;
                    $('#previmg').append(`
                        <img  src="` + prevSrc + `" alt = "" style="height: 80px" />
                    `);

                    $('#EditSlider').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update slider
        $('#EditSlider').submit(function(e) {
            e.preventDefault();
            let sliderId = $('#slider_id').val();

            $.ajax({
                type: 'POST',
                url: 'slider/' + sliderId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    if (data == 'error') {
                        swal({
                            icon: 'error',
                            title: 'Can not update Slider',
                            text: 'Please fill Title Name',
                            buttons: true,
                            buttons: "Thanks",
                        });
                    } else {
                        $('#EditSlider').find('#slider_small_title').val('');
                        $('#EditSlider').find('#slider_title').val('');
                        $('#EditSlider').find('#slider_text').val('');
                        $('#EditSlider').find('#slider_btn_name').val('');
                        $('#EditSlider').find('#slider_btn_link').val('');
                        $('#EditSlider').find('#slider_image').val('');
                        $('#previmg').html('');

                        swal({
                            title: "Slider update successfully !",
                            icon: "success",
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                        });
                        sliderinfo.ajax.reload();
                    }

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete slider

        $(document).on('click', '#deleteSliderBtn', function() {
            let sliderId = $(this).data('id');
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
                            url: 'sliders/' + sliderId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Slider has been deleted!", {
                                    icon: "success",
                                });
                                sliderinfo.ajax.reload();
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

        // status update

        $(document).on('click', '#sliderstatusBtn', function() {
            let sliderId = $(this).data('id');
            let sliderStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'slider/status',
                data: {
                    slider_id: sliderId,
                    status: sliderStatus,
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
                    sliderinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

    });
</script>

@endsection
