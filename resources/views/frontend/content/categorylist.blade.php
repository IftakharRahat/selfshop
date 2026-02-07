@extends('frontend.master')

@section('maincontent')
<div class="container-fluid pb-5 pt-3">
    <div class="text-center pb-3">
        <h2 class="section-title px-5"><span class="px-2" style="border-bottom:none">Our Category List</span></h2>
    </div>
    <div class="row px-xl-5">
        @forelse ($categorylists as $categorylist)
            <div class="col-6 col-lg-2">
                <div class="cat-item mb-3 d-flex flex-column border-new" id="categoryItem">
                    <a href="{{ url('/product/category/' . $categorylist->slug) }}"
                        class="cat-img position-relative overflow-hidden text-center">
                        <img class="img-fluid" src="{{ asset($categorylist->category_icon) }}" alt="" >
                    </a>
                    <a href="{{ url('/product/category/' . $categorylist->slug) }}">
                        <h5 class="font-weight-semi-bold m-0" id="categoryItemNameall">
                            {{ $categorylist->category_name }}
                        <br>
                        <small>products ({{ App\Models\Product::where('category_id',$categorylist->id)->get()->count() }})</small>
                        </h5>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-md-3"></div>
            <div class="col-md-6 col-12" style="text-align: center; padding: 20px; border: dotted;">
                <h1>No Category Found !</h1>
            </div>
            <div class="col-md-3"></div>

        @endforelse
    </div>
</div>


<style>

        #orderConfirm{
        font-size:18px !important;
        padding: 12px;
        border-radius: 5px;
    }
    #closebtn{
        font-size: 18px;
        padding: 12px;
        float: right;
        border-radius: 5px;
    }
            #poductview{
        padding-left: 4px;
        padding-right: 6px !important;
    }

    #ordernowmodal{
        padding-left: 4px;
        padding-right: 6px !important;
    }

    #sync1 {
        .item {
            background: #0c83e7;
            padding: 80px 0px;
            margin: 5px;
            color: #FFF;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
            text-align: center;
        }
    }

    #sync2 {
    .item {
        background: #C9C9C9;
        padding: 10px 0px;
        margin: 5px;
        color: #FFF;
        -webkit-border-radius: 3px;
        -moz-border-radius: 3px;
        border-radius: 3px;
        text-align: center;
        cursor: pointer;
        h1 {
        font-size: 18px;
        }
    }
    .current .item{
        background: #0c83e7;
    }
    }



    .owl-theme {
        .owl-nav {
            /*default owl-theme theme reset .disabled:hover links */
            [class*='owl-'] {
            transition: all .3s ease;
            &.disabled:hover {
            background-color: #D6D6D6;
            }
            }

        }
    }

    @media only screen and (max-width: 768px) {
        #orderConfirm{
            font-size:22px !important;
            padding: 10px;
        }
    }



</style>



@endsection
