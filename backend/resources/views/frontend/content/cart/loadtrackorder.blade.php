@extends('frontend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-Track Order
@endsection

<style>
    .navbar-wrapper{
        display: none !important;
    }
</style>
<!-- /.breadcrumb -->
<div class="body-content outer-top-xs">

    <section class="mt-1 mb-3" style="padding-bottom:30px">
        <div class="container">
            <div class="px-2 py-1 p-md-3 bg-white shadow-sm">
                <div class="search-area pb-4">
                    <h4 class="m-0 text-center pb-4"> <b>Your Order Tracking History</b> </h4>

                </div>
            </div>

            @if (isset($orders->trackingLink))
            <embed type="text/html" src="{{$orders->trackingLink}}" width="100%" height="1200px">

            @else
                <h1 style="text-align: center;padding-top: 20px;"> Nothing Found</h1>
            @endif
        </div>
    </section>

</div>

    <script>
        $(document).ready(function() {



        });
    </script>

<style>
    .process-steps {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .process-steps li {
        width: 25%;
        float: left;
        text-align: center;
        position: relative;
    }

    .process-steps li .icon {
        height: 30px;
        width: 30px;
        margin: auto;
        background: #fff;
        border-radius: 50%;
        line-height: 30px;
        font-size: 14px;
        font-weight: 700;
        color: #adadad;
        position: relative;
    }

    .process-steps li .title {
        font-weight: 600;
        font-size: 13px;
        color: #777;
        margin-top: 8px;
        margin-bottom: 0;
    }

    .process-steps li+li:after {
        position: absolute;
        content: "";
        height: 3px;
        width: calc(100% - 30px);
        background: #fff;
        top: 14px;
        z-index: 0;
        right: calc(50% + 15px);
    }

    .breadcrumb {
        padding: 5px 0;
        border-bottom: 1px solid #e9e9e9;
        background-color: #fafafa;
    }

    .search-area .search-button {
        border-radius: 0px 3px 3px 0px;
        display: inline-block;
        float: left;
        margin: 0px;
        padding: 5px 15px 6px;
        text-align: center;
        background-color: #e62e04;
        border: 1px solid #e62e04;
    }

    .search-area .search-button:after {
        color: #fff;
        content: "\f002";
        font-family: fontawesome;
        font-size: 16px;
        line-height: 9px;
        vertical-align: middle;
    }
</style>

@endsection
