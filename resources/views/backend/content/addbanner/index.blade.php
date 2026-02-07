@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Add Banners
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
                <div class="d-flex align-items-center justify-content-between" style="width: 100%;float:left;">
                    <h6 class="mb-0">Addbanners List</h6>
                    <a class="btn btn-info" href="{{ route('admin.addbanners.create') }}">Create Banner</a>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 rounded bg-secondary h-100">
                <div class="data-tables">
                    <table class="table table-dark" id="sliderinfo" width="100%" style="text-align: center;">
                        <thead class="thead-light">
                            <tr>
                                <th>SL</th>
                                <th>Title</th>
                                <th>Icon</th>
                                <th>status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($addbanners as $addbanner)
                                <tr class="">
                                    <td>{{ $addbanner->id }}</td>
                                    <td>{{ $addbanner->title }}</td>
                                    <td>
                                        <img src="{{ asset($addbanner->icon) }}" alt="" srcset=""
                                            style="height: 50px;">
                                    </td>
                                    <td>
                                        @if ($addbanner->status == 'Active')
                                            <form action="{{ url('admin/addbanner/status/' . $addbanner->id) }}"
                                                method="post">
                                                @method('PUT')
                                                @csrf
                                                <input type="text" name="status" value="Inactive" hidden>
                                                <button type="submit" class="btn btn-info">Active</button>
                                            </form>
                                        @else
                                            <form action="{{ url('admin/addbanner/status/' . $addbanner->id) }}"
                                                method="post">
                                                @method('PUT')
                                                @csrf
                                                <input type="text" name="status" value="Active" hidden>
                                                <button type="submit" class="btn btn-warning">Inactive</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.addbanners.edit', $addbanner->id) }}" type="button"
                                            class="mt-2 btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- edit add banner --}}
        <div class="modal fade" id="editmainSlider" tabindex="-1">
            <div class="modal-dialog">
                <div class="rounded modal-content bg-secondary h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Edit Slider</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="EditSlider" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3 form-floating">
                                <input type="text" class="form-control" name="slider_title" id="slider_title"
                                    placeholder="Title" required>
                                <label for="floatingInput">Title</label>
                            </div>
                            <div class="mb-3 form-floating">
                                <input type="text" class="form-control" name="slider_small_title"
                                    id="slider_small_title" placeholder="Small Title">
                                <label for="floatingInput">Small Title</label>
                            </div>
                            <div class="mb-3 form-floating">
                                <textarea class="form-control" placeholder="Text" name="slider_text" id="slider_text" style="height: 80px;"></textarea>
                                <label for="floatingTextarea">Text</label>
                            </div>
                            <div class="mb-3 form-floating">
                                <input type="text" class="form-control" name="slider_btn_name" id="slider_btn_name"
                                    placeholder="Button Name">
                                <label for="floatingInput">Button Name</label>
                            </div>
                            <div class="mb-3 form-floating">
                                <input type="text" class="form-control" name="slider_btn_link" id="slider_btn_link"
                                    placeholder="Button Link">
                                <label for="floatingInput">Button Link</label>
                            </div>
                            <div class="mt-4 mb-4">
                                <input class="form-control form-control-lg bg-dark" name="slider_image"
                                    id="slider_image" type="file">
                            </div>
                            <input type="text" name="slider_id" id="slider_id" hidden>

                            <div class="m-3 mb-0 ms-0"
                                style="text-align: center;height: 100px;margin-top:20px !important">
                                <h4 style="width:30%;float: left;text-align: left;">Image : </h4>
                                <div id="previmg" style="float: left;"></div>
                            </div>
                            <br>
                            <div class="mt-2 form-group" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn" data-bs-dismiss="modal"
                                        class="btn btn-dark btn-block" style="float: left">Close</button>
                                    <button type="submit" name="btn"
                                        class="btn btn-primary AddCourierBtn btn-block">Update</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

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
                    $('#previmg').append(`
                        <img  src="../` + data.slider_image + `" alt = "" style="height: 80px" />
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
