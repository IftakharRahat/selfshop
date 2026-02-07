@extends('frontend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-{{ $title }}
@endsection


<div class="container">
    <div class="pb-4 mt-4 mb-4 row">
        <div class="p-0 col-12">
            <div class="p-2 body-content outer-top-xs" style="background: white !important;">

                {!! $value->value !!}

            </div>
        </div>

    </div>
</div>
</div>

@endsection
