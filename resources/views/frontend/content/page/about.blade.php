@extends('frontend.master')

@section('maincontent')
    {{-- //contact us page --}}
    <?php

    $pagebanner =App\Models\Pagebanner::where('id',1)->where('status','Active')->first();

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


    <section class="mb-4 my-hm-cat pt-4 mt-4">
        <div class="container">
            @forelse ($abouts as $about)
                <div class="row">
                    <div class="col-md-12 xs-heading wow fadeIn animated" style="text-align:center;visibility: visible; animation-name: fadeIn;">
                        <h3 class="heading-title"> <span style="text-align: center">{{ $about->title }}</span></h3>
                    </div>
                    <br>
                    <div class="col-md-6" style="text-align: center">
                        <img src="{{ asset($about->image) }}" alt="" style="height:200px">
                    </div>
                    <div class="col-md-6" style="    padding: 24px;">
                        <div class="text-light">
                            {!! $about->text !!}
                        </div>
                    </div>
                    <br>

                </div>
                <div class="row" style="text-align: left">
                    <div class="col-md-1"></div>
                    <div class="col-md-8">
                        <div class="text-light">
                            {!! $about->description !!}
                        </div>
                    </div>
                    <div class="col-md-1"></div>
                </div>
            @empty

            @endforelse
        </div>


    </section>


    <style>
        .xs-heading .heading-title {
            font-size: 2.11765rem;
            font-weight: 700;
        }

    </style>
@endsection
