@extends('frontend.master')

@section('maincontent')
    {{-- //team banner--}}
    <?php

    $pagebanner =App\Models\Pagebanner::where('id',5)->where('status','Active')->first();

    ?>
    @if (isset($pagebanner->page_image))
        <section class="mb-2 my-hm-cat">
            <div class="">
                <div class="arrow-round gutters-5" >
                    <img src="{{asset($pagebanner->page_image)}}" alt="" style="height: 300px;width:100%">
                </div>
            </div>

        </section>
    @else

    @endif

    <br>
    <br>

    <div class="section_home_categories">
        {{-- //top sell product --}}
        @include('frontend.includes.subincludes.team')
    </div>


    <style>
        .xs-heading .heading-title {
            font-size: 2.11765rem;
            font-weight: 700;
        }

    </style>
@endsection
