@extends('frontend.master')

@section('maincontent')

    <div class="container pb-5 mt-4 mb-4 mb-sm-4">
        <div class="pt-5 pb-5" style="margin-bottom:5px">
            <div class="py-3 card mt-sm-3" style="border-radius: 6px;">
                <div class="text-center card-body">
                    <img src="{{asset('public/success.svg')}}" alt="" style="width: 100px;">
                    <br><br>
                    <h2 class="pb-3 h4" style="color:black">ধন্যবাদ! আপনার অর্ডারটি সফলভাবে সম্পন্ন হয়েছে।
                    <br>
                    <br>
                    </h2>
                    <h2 style="font-weight: 300;font-size: 20px;" class="pb-4 mb-4">
                    প্রিয় গ্রাহক,
                    <br>
                    <br>
                    আপনার অর্ডার গ্রহণ করা হয়েছে এবং আমরা দ্রুততম সময়ের মধ্যে এটি প্রসেস করার জন্য কাজ করছি। আমাদের টিম খুব শিগগিরই আপনার অর্ডার সম্পর্কে আপডেট জানাবে।
                    <br>
                    <br>
                    আপনার বিশ্বাস ও সমর্থনের জন্য আমরা কৃতজ্ঞ। যদি কোনো প্রশ্ন থাকে বা সহায়তা প্রয়োজন হয়, অনুগ্রহ করে আমাদের কাস্টমার সাপোর্টের সাথে যোগাযোগ করুন।
                    <br>
                    <br>
                    SelfShop এর সাথে থাকার জন্য আপনাকে ধন্যবাদ!
                    </h2>
                    <h2 class="pb-3 h4" style="color:black">যেকোন প্রয়োজনে কল করুন: <a href="tel: {{App\Models\Basicinfo::first()->phone_one}}">  {{App\Models\Basicinfo::first()->phone_one}}</a></h2>
                     <span class="text-center">
                        <h2 class="h4" style="    margin: auto;color: #E7005E;padding:8px;width:max-content;border-radius: 8px;background: #FFE5E5;"> অর্ডার আইডি: {{Session::get('invoiceID')}}
                        <input type="text" value="{{ Session::get('invoiceID') }}" id="email{{Session::get('invoiceID')}}" hidden >
                        <button class="btn btn-primary" style="color: white;border-radius: 8px;font-weight: bold;" id="copyemail" data-id="{{ Session::get('invoiceID') }}"><i class="fas fa-copy"></i></button></h2>
                     </span>
                     <br>

                    <a class="mt-3 btn btn-primary" href="{{url('/')}}" style="color:#fff;border-radius: 6px;">পণ্য ব্রাউজ করুন</a>
                </div>
            </div>
        </div>
    </div>


    <script>
    $(document).ready(function() {

        $(document).on('click', '#copyemail', function(e) {
            var id = $(this).attr("data-id");
            var divid = 'email' + id;
            var copyText = document.getElementById(divid);

            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard
                .writeText(copyText.value)
                .then(() => {
                    alert("ইনভয়েস আইডি সফলভাবে কপি হয়েছে");
                })
                .catch(() => {
                    alert("কিছু সমস্যা হয়েছে");
                });
        });

    });
</script>

@endsection