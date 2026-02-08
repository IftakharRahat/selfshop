@extends('frontend.master')

@section('maincontent')
    {{-- //contact us page --}}
    <?php

    $pagebanner =App\Models\Pagebanner::where('id',4)->where('status','Active')->first();

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

            <br>
            <br>
            <div class="row" style="text-align: center">
                @forelse ($returnpolicys as $returnpolicy)
                    <div class="col-12 p-2">
                        <div class="xs-heading wow fadeIn animated" style="text-align:center;visibility: visible; animation-name: fadeIn;">
                            <h3 class="heading-title" style="float: left;"><span style="color:black;font-weight:bold">{{ $returnpolicy->title }}</span></h3>
                        </div>
                    </div>
                    <div class="col-12" style="text-align: left">
                        {!! $returnpolicy->text !!}
                    </div>
                @empty

                @endforelse
            </div>
        </div>


    </section>


    <style>
        .xs-heading .heading-title {
            font-size: 2.11765rem;
            font-weight: 700;
        }

    </style>
@endsection
