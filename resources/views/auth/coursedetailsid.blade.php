@extends('user.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-Courses
@endsection

<style>
    #profileImage {
        border-radius: 50%;
        padding: 65px;
        padding-bottom: 8px;
        padding-top: 10px;
    }

    .sidebar-widget-title {
        position: relative;
    }

    .sidebar-widget-title:before {
        content: "";
        width: 100%;
        height: 1px;
        background: #eee;
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
    }

    .py-3 {
        padding-bottom: 1rem !important;
    }

    .sidebar-widget-title span {
        background: #fff;
        text-transform: uppercase;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.2em;
        position: relative;
        padding: 8px;
        color: #dadada;
    }

    ul.categories {
        padding: 0;
        margin: 0;
        list-style: none;
    }

    ul.categories--style-3>li {
        border: 0;
    }

    ul.categories>li {
        border-bottom: 1px solid #f1f1f1;
    }

    .widget-profile-menu a i {
        opacity: 0.6;
        font-size: 13px !important;
        top: 0 !important;
        width: 18px;
        height: 18px;
        text-align: center;
        line-height: 18px;
        display: inline-block;
        margin-right: 0.5rem !important;
    }

    .category-name {
        color: black;
        font-size: 18px;
    }

    .category-icon {
        font-size: 18px;
        color: black;
    }

    .bg-baguni{
        background-color: #613EEA !important;
    }
    .bg-return{
        background-color: #9C0000 !important;
    }

    .bg-delivered{
        background-color: #14BF7D !important;
    }
    .bg-confirmed{
        background-color: #004328 !important;
    }
    .bg-ondv{
        background-color: #D4911D !important;
    }
    .bg-primaryss {
        background-color: #0a296f !important;
    }

</style>


    <div class="container pt-4">
        <div class="row">
            <div class="p-0 m-auto col-lg-12">
                <div class="p-2 pt-0">
                    <div class="container">
                        <div class="mb-3 row">
                            <div class="col-md-12">
                                <a href="{{ url()->previous() }}" class="mb-2 btn btn-info btn-sm" style="border-radius: 4px;">
                                    <img src="{{asset('public/arrow-o.png')}}" style="padding-right: 10px;width: 30px;">All Course
                                </a>
                            </div>
                            <div class="m-auto mb-2 col-12 col-lg-12">
                                <div class="card" style="border-radius:4px">
                                    <div class="p-2 card-body">
                                        @if($coursedetails->youtube_embade=='')
                                        <img src="{{asset($coursedetails->course_image)}}" style="width:100%;min-height: 185px;border-radius: 6px;">
                                        @else
                                        <iframe width="100%" height="200px" style="border:none;border-radius:6px;"
                                            src="https://www.youtube.com/embed/{{$coursedetails->youtube_embade}}">
                                        </iframe>
                                        @endif
                                    </div>
                                    <div class="p-2 card-footer d-flex justify-content-between">
                                        <div class="play">
                                            <p class="m-0" style="color: #000;font-family: Roboto;font-size: 14px;font-style: normal;font-weight: 600;">{{$coursedetails->course_name}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="mb-3 row">
                            @forelse($courses as $key=>$course)
                                <div class="mb-2 col-12 col-lg-6">
                                    <a href="{{url('user/coursedetails',$course->id)}}">
                                        <div class="card" style="border-radius:4px">
                                            <div class="p-2 card-body d-flex">
                                                @if($course->youtube_embade=='')
                                                <img src="{{asset($course->course_image)}}" style="width:100%;min-height: 185px;border-radius: 6px;">
                                                @else
                                                <iframe width="165px" height="93px" style="border:none;border-radius:6px;"
                                                    src="https://www.youtube.com/embed/{{$course->youtube_embade}}">
                                                </iframe>
                                                @endif

                                                <div class="pl-2 play">
                                                    <p class="m-0">Class :0{{$key+1}}</p>
                                                    <p class="m-0" style="color: #000;font-family: Roboto;font-size: 14px;font-style: normal;">{{$course->course_name}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @empty

                            @endforelse
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
