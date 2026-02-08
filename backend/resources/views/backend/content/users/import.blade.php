@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}- Users
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

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="h-100 bg-secondary rounded p-4 pb-0">
                <div class="d-flex align-items-center justify-content-between"  style="width: 50%;float:left;">
                    <h6 class="mb-0">Users Import</h6>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="bg-secondary rounded h-100 p-4">
                <form action="{{ route('import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-4">
                        <div class="custom-file text-left">
                            <label class="" for="customFile">Choose file</label>
                            <input type="file" name="file" class="form-control" id="customFile">
                        </div>
                    </div>
                    <button class="btn btn-primary">Import File</button>
                </form>
            </div>
        </div>


    </div>
</div>


@endsection
