@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Edit Add Banners
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
                    <h6 class="mb-0">Addbanners List</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 rounded bg-secondary h-100">
                <form name="form" action="{{ route('admin.addbanners.update', $addbanner->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3 form-floating">
                        <input type="text" value="{{ $addbanner->title }}" class="form-control" name="title" id="title" placeholder="Title">
                        <label for="floatingInput">Title</label>
                    </div>
                    <div class="mb-3 form-group">
                        <label for="floatingInput">Title</label>
                        <textarea name="text" class="form-control" id="text" cols="30" rows="3">{{ $addbanner->text }}</textarea>
                    </div>
                    <div class="mt-4 mb-4">
                        <label for="floatingInput">Icon</label>
                        <input class="form-control form-control-lg bg-dark" name="icon" id="icon" type="file">
                    </div>
                    <div class="m-3 mb-0 ms-0" style="text-align: center;height: 170px;margin-top:20px !important">
                        <h4 style="width:30%;float: left;text-align: left;">Icon : </h4>
                        <div id="previmg" style="float: left;width:50%">
                            <img src="{{ asset($addbanner->icon) }}" alt="" srcset="" style="height: 140px;">
                        </div>
                    </div>
                    <br>
                    <div class="mt-4 mb-4">
                        <label for="floatingInput">Background Image</label>
                        <input class="form-control form-control-lg bg-dark" name="bg_img" id="bg_img" type="file">
                    </div>

                    <div class="m-3 mb-0 ms-0" style="text-align: center;height: 170px;margin-top:20px !important">
                        <h4 style="width:30%;float: left;text-align: left;">Background Image : </h4>
                        <div id="previmg" style="float: left;width:70%">
                            <img src="{{ asset($addbanner->bg_img) }}" alt="" srcset="" style="height: 140px;">
                        </div>
                    </div>
                    <br>
                    <div class="pt-4 mt-4 form-group" style="text-align: right">
                        <div class="submitBtnSCourse">
                            <a href="{{ route('admin.addbanners.index') }}" class="btn btn-dark btn-block"
                                style="float: left">Close</a>
                            <button type="submit" name="btn"
                                class="btn btn-primary AddCourierBtn btn-block">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>


@endsection
