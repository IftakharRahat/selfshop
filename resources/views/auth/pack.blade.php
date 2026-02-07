<div class="card" id="packagetab" style="padding: 8px;border-radius: 6px;">
    <div class="d-flex justify-content-between">
        @forelse ($packages as $package)
            <a class="silver" @if($pack->id == $package->id) id="indtab" @endif onclick="loadpack('{{ $package->id }}')">
                @if ($package->id==1)
                <img src="{{ asset('public/silver.png') }}" alt="">
                @elseif($package->id==2)
                <img src="{{ asset('public/gold.png') }}" alt="">
                @else
                <img src="{{ asset('public/platinum.png') }}" alt="">
                @endif
                <span style="color:black">{{ $package->package_name }} &nbsp;</span>

            </a>
        @empty

        @endforelse
    </div>
</div>

@if ($pack->id=='1')
    <div class="card mt-3" id="packagetab" style="border-radius: 15px;border: 1px solid #613EEA;background: #F4F4F4;padding: 8px;border-radius: 6px;">
        <div class="d-flex mb-3" style="border-bottom:1px solid #613EEA;justify-content: center;">
            @if (isset($pack->discount_price))
                <h4 style="font-size: 35px;"><small  style="font-size: 22px; color:red;"><del>৳ {{ $numto->bnNum($pack->price) }}</del></small> ৳ {{ $numto->bnNum($pack->discount_price) }} <small  style="font-size: 16px;">/ {{ $numto->bnNum($pack->validity) }} মাস</small></h4>
            @else
            <h4 style="font-size: 35px;"> ৳ {{ $numto->bnNum($pack->price) }} <small  style="font-size: 16px;">/ {{ $numto->bnNum($pack->validity) }} মাস</small></h4>
            @endif
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; আকর্ষণীয় ড্যাশবোর্ড</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; অর্ডার ম্যানেজমেন্ট সিস্টেম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; রেফারেল ইনকাম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; এড টু শপ অপশন</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; অর্ডার বোনাস</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; মাসিক অর্ডার রিপোর্ট</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; ফ্রি ভিডিও কোর্স</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; ফ্রড চেকার</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; টিম মেম্বার</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; প্রোডাক্ট রিকোয়েস্ট</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp;টিকেটিং সিস্টেম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp;অর্ডার এনালাইটিক</span>
        </div>

@elseif ($pack->id=='2')

    <div class="card mt-3" id="packagetab" style="border-radius: 15px;border: 1px solid #613EEA;background: #FFF3D1;padding: 8px;border-radius: 6px;">
        <div class="d-flex mb-3" style="border-bottom:1px solid #613EEA;justify-content: center;">
            @if (isset($pack->discount_price))
                <h4 style="font-size: 35px;"><small  style="font-size: 22px; color:red;"><del>৳ {{ $numto->bnNum($pack->price) }}</del></small> ৳ {{ $numto->bnNum($pack->discount_price) }} <small  style="font-size: 16px;">/ {{ $numto->bnNum($pack->validity) }} মাস</small></h4>
            @else
            <h4 style="font-size: 35px;"> ৳ {{ $numto->bnNum($pack->price) }} <small  style="font-size: 16px;">/ {{ $numto->bnNum($pack->validity) }} মাস</small></h4>
            @endif
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; আকর্ষণীয় ড্যাশবোর্ড</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; অর্ডার ম্যানেজমেন্ট সিস্টেম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; রেফারেল ইনকাম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; এড টু শপ অপশন</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; অর্ডার বোনাস</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; মাসিক অর্ডার রিপোর্ট</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; ফ্রি ভিডিও কোর্স</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; ফ্রড চেকার</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp;  ম্যানেজ টিম মেম্বার</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; প্রোডাক্ট রিকোয়েস্ট</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp;টিকেটিং সিস্টেম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/cros.png') }}" id="checkimg">
            <span> &nbsp; &nbsp;অর্ডার এনালাইটিক</span>
        </div>

@else

    <div class="card mt-3" id="packagetab" style="border-radius: 15px;border: 1px solid #613EEA;background: #D9E1FF;padding: 8px;border-radius: 6px;">
        <div class="d-flex mb-3" style="border-bottom:1px solid #613EEA;justify-content: center;">
            @if (isset($pack->discount_price))
                <h4 style="font-size: 35px;"><small  style="font-size: 22px; color:red;"><del>৳ {{ $numto->bnNum($pack->price) }}</del></small> ৳ {{ $numto->bnNum($pack->discount_price) }} <small  style="font-size: 16px;">/ {{ $numto->bnNum($pack->validity) }} মাস</small></h4>
            @else
            <h4 style="font-size: 35px;"> ৳ {{ $numto->bnNum($pack->price) }} <small  style="font-size: 16px;">/ {{ $numto->bnNum($pack->validity) }} মাস</small></h4>
            @endif
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; আকর্ষণীয় ড্যাশবোর্ড</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; অর্ডার ম্যানেজমেন্ট সিস্টেম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; রেফারেল ইনকাম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; এড টু শপ অপশন</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; অর্ডার বোনাস</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; মাসিক অর্ডার রিপোর্ট</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; ফ্রি ভিডিও কোর্স</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; ফ্রড চেকার</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; টিম মেম্বার</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp; প্রোডাক্ট রিকোয়েস্ট</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp;টিকেটিং সিস্টেম</span>
        </div>
        <div class="d-flex">
            <img src="{{ asset('public/tik.png') }}" id="checkimg">
            <span> &nbsp; &nbsp;অর্ডার এনালাইটিক</span>
        </div>

@endif
    <br>
    <form action="{{ url('purchese-package') }}" method="POST">
        @csrf
        <input type="text" name="package_id" id="package_id" value="{{ $pack->id }}" hidden>
        @if (isset($pack->discount_price))
            <input type="text" name="amount" id="amount" value="{{ $pack->discount_price }}" hidden>
        @else
            <input type="text" name="amount" id="amount" value="{{ $pack->price }}" hidden>
        @endif

        <button type="submit" class="btn btn-info" style="border-radius: 5px;border:1px solid #F16E52;width: 100%;background: #F16E52;">পেমেন্ট করুন</button>
    </form>
</div>
