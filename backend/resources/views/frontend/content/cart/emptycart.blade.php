@extends('frontend.master')

@section('maincontent')

    <div class="container pb-5 mb-sm-4">
        <div class="pt-5 pb-5" style="margin-bottom:5px">
            <div class="py-3 card mt-sm-3">
                <div class="text-center card-body">
                    <h2 class="pb-3 h4">সম্মানিত গ্রাহক, আপনার কার্ট-এ কোনো পন্য যোগ করা নেই। অনুগ্রহপূর্বক, আপনার পছন্দের পন্যটি বাছাই করুন এবং অর্ডার করে SelfShop এর পাশেই থাকুন। যে কোনো প্রয়োজনে সরাসরি কল করুন :- <a href="tel:{{App\Models\Basicinfo::first()->phone_one}}">{{App\Models\Basicinfo::first()->phone_one}}   </a> এই নাম্বারে |</h2>
                    <a class="mt-3 btn btn-primary" href="{{ url('/') }}" style="color:white">হোম পেজে ফিরে যাই</a>
                </div>
            </div>
        </div>
    </div>
@endsection
