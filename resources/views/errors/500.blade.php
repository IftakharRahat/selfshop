@extends('frontend.master')

@section('maincontent')
    <div class="body-content outer-top-bd">
        <div class="container">
            <div class="x-page inner-bottom-sm" style="padding-bottom: 80px;">
                <div class="row">
                    <div class="col-lg-8 m-auto x-text text-center"> 
                         <img src="{{asset('public/500image.gif')}}" style="width:100%">
                         <br>
                        <a href="{{ url('/') }}" class="btn btn-info"><i class="fa fa-home"></i> Go To Homepage</a>
                    </div>
                </div><!-- /.row -->
            </div><!-- /.sigin-in-->
        </div><!-- /.container -->
    </div><!-- /.body-content -->
@endsection
