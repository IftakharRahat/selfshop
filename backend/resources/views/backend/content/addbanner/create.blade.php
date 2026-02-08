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
                <div class="d-flex align-items-center justify-content-between" style="width: 50%;float:left;">
                    <h6 class="mb-0">Addbanners Create</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="p-4 rounded bg-secondary h-100">
                <form name="form" action="{{ url('admin/addbanners') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3 form-floating">
                        <input type="text" class="form-control" name="title" id="title" placeholder="Title">
                        <label for="floatingInput">Title</label>
                    </div>
                    <div class="mb-3 form-group">
                        <label for="floatingInput">Title</label>
                        <textarea name="text" class="form-control" id="text" cols="30" rows="3"></textarea>
                    </div>
                    <div class="mt-4 mb-4">
                        <label for="floatingInput">Icon</label>
                        <input class="form-control form-control-lg bg-dark" name="icon" id="icon" type="file" required>
                    </div>
                    <div class="mt-4 mb-4">
                        <label for="floatingInput">Background Image</label>
                        <input class="form-control form-control-lg bg-dark" name="bg_img" id="bg_img" type="file" required>
                    </div>
                    <br>
                    <div class="pt-4 mt-4 form-group" style="text-align: right">
                        <div class="submitBtnSCourse">
                            <a href="{{ route('admin.addbanners.index') }}" class="btn btn-dark btn-block"
                                style="float: left">Close</a>
                            <button type="submit" name="btn"
                                class="btn btn-primary AddCourierBtn btn-block">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>


@endsection
