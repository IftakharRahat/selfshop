@extends('frontend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-About Us
@endsection
 <style>
    .about-section {
      background: url('public/aboutbanner.png') no-repeat center center;
      background-size: cover;
      position: relative;
      padding: 70px 0;
      color: #fff;
    }

    .about-footer-section {
      background: linear-gradient(0deg, rgba(132, 3, 56, 0.37) 0%, rgba(132, 3, 56, 0.37) 100%),
              url('public/aboutfooter.jpg');
      background-size: cover;
      position: relative;
      padding: 100px 0;
      color: #fff;
    }

    .about-section::before {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.6); /* Dark overlay */
      z-index: 0;
    }

    .about-content {
      position: relative;
      z-index: 1;
      max-width: 900px;
      margin: auto;
    }

    .about-title {
      font-weight: 700;
      font-size: 3rem;
    }

    .about-text {
        font-size: 1.2rem;
        line-height: 1.5;
        margin-top: 20px;
        text-align: justify;
    }

    @media (max-width: 768px) {
      .about-title {
        font-size: 2rem;
      }

      .about-text {
        font-size: 1rem;
      }
    }

    .line {
        position: absolute;
        left: 50%;
        top: 0;
        width: 2px;
        height: 100%;
        background-color: white; /* Or any color */
        opacity: 0.8;
    }

  </style>

    <div class="container-fluid about-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <section class="text-center text-white">

                        <h2 class="about-title" style="color:white;text-align:left">About SelfShop</h2>
                        <p class="about-text">
                            SelfShop is an innovative online platform dedicated to empowering entrepreneurs, dropshippers,
                            and resellers with high-quality products and an exceptional shopping experience.
                            Established with the vision of transforming the eCommerce landscape,
                            SelfShop has rapidly grown to a community of over 32,000 users who leverage
                            our platform to build and expand their businesses. Our Trade Licence number is: 1258
                        </p>

                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5 mt-4">
        <div class="row">
            <div class="col-lg-5">
                <img src="{{asset('public/ab1.png')}}" alt="" style="width:100%">
            </div>
            <div class="col-lg-7">
                <section class="pt-4 mt-lg-4">

                    <h2 style="text-align:left"><b>Our Mission At SelfShop</b></h2>
                    <p style="font-size: 18px;color:black;text-align:justify">
                        At SelfShop, our mission is to provide a seamless and profitable eCommerce experience for entrepreneurs worldwide. We are committed to offering a diverse range of products, cutting-edge tools, and business solutions that simplify online selling. Through our dedication to innovation and customer satisfaction, we aim to foster a thriving community of business owners who can scale their ventures effortlessly.
                    </p>

                </section>
            </div>
        </div>
    </div>

    <div class="container-fluid" style="background: #FDF0F6;">
        <div class="container py-5 mt-4">
            <div class="row">
                <div class="m-auto col-lg-9">
                    <h2 style="text-align:center"><b>What We Offer</b></h2>
                    <p style="text-align:center">We specialize in curating a broad selection of trending and high-demand products tailored to entrepreneurs and online resellers. Our product range includes:</p>
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-lg-3">
                    <div class="text-center card card-body" style="background:none;border-radius: 12px;border: 1px solid #CACACA;">
                        <div class="imgdata">
                            <img src="{{asset('public/1a.svg')}}" alt="" style="width: 46px;border: 1px solid;border-radius: 4px;padding: 2px;">
                        </div>
                        <p class="mt-2 mb-0" style="color: black;font-weight: 500;    font-size: 15px;">Dropshipping-friendly merchandise</p>
                    </div>
                </div>
                <div class="mb-3 col-lg-3">
                    <div class="text-center card card-body" style="background:none;border-radius: 12px;border: 1px solid #CACACA;">
                        <div class="imgdata">
                            <img src="{{asset('public/2a.svg')}}" alt="" style="width: 46px;border: 1px solid;border-radius: 4px;padding: 2px;">
                        </div>
                        <p class="mt-2 mb-0" style="color: black;font-weight: 500;    font-size: 15px;">Wholesale items for resellers</p>
                    </div>
                </div>
                <div class="mb-3 col-lg-3">
                    <div class="text-center card card-body" style="background:none;border-radius: 12px;border: 1px solid #CACACA;">
                        <div class="imgdata">
                            <img src="{{asset('public/3a.svg')}}" alt="" style="width: 46px;border: 1px solid;border-radius: 4px;padding: 2px;">
                        </div>
                        <p class="mt-2 mb-0" style="color: black;font-weight: 500;    font-size: 15px;">Digital tools for eCommerce management</p>
                    </div>
                </div>
                <div class="mb-3 col-lg-3">
                    <div class="text-center card card-body" style="background:none;border-radius: 12px;border: 1px solid #CACACA;">
                        <div class="imgdata">
                            <img src="{{asset('public/4a.svg')}}" alt="" style="width: 46px;border: 1px solid;border-radius: 4px;padding: 2px;">
                        </div>
                        <p class="mt-2 mb-0" style="color: black;font-weight: 500;    font-size: 15px;">Ensure quality, reliability, and market relevance</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5 my-4">
        <div class="row">
            <div class="col-lg-6">
                <h2 style="text-align:left"><b>Why Choose Us</b></h2>
                <br>
                <div id="accordion" class="mb-4">
                    <div class="mb-2 card" style="border-radius: 16px;border: 1px solid #DBDBDB;background: rgba(244, 244, 244, 0.36);">
                        <div class="card-header" id="headingOne" style="background: none;">
                        <h5 class="mb-0">
                            <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne"  style="color: #555;text-decoration: none;width: 100%;text-align: left;padding: 0;">
                            Quality Assurance &nbsp;&nbsp;&nbsp;&nbsp; <span class="arrow-icon">&gt;</span>
                            </button>
                        </h5>
                        </div>

                        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                        <div class="card-body">
                            We partner with reputable suppliers to guarantee top-tier products.
                        </div>
                        </div>
                    </div>
                    <div class="mb-2 card" style="border-radius: 16px;border: 1px solid #DBDBDB;background: rgba(244, 244, 244, 0.36);">
                        <div class="card-header" id="headingTwo" style="background: none;">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo"  style="color: #555;text-decoration: none;width: 100%;text-align: left;padding: 0;">
                            Entrepreneur-Centric Services &nbsp;&nbsp;&nbsp;&nbsp; <span class="arrow-icon">&gt;</span>
                            </button>
                        </h5>
                        </div>
                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                        <div class="card-body">
                            Entrepreneur-Centric Services are tailored solutions designed to support business founders at every stage — from startup to scale-up.
                        </div>
                        </div>
                    </div>
                    <div class="mb-2 card" style="border-radius: 16px;border: 1px solid #DBDBDB;background: rgba(244, 244, 244, 0.36);">
                        <div class="card-header" id="headingThree" style="background: none;">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="color: #555;text-decoration: none;width: 100%;text-align: left;padding: 0;">
                            Secure Transactions &nbsp;&nbsp;&nbsp;&nbsp; <span class="arrow-icon">&gt;</span>
                            </button>
                        </h5>
                        </div>
                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                        <div class="card-body">
                            Secure Transactions ensure that all payments and data exchanges are protected using advanced encryption and security protocols, giving customers confidence and protecting businesses from fraud or unauthorized access.
                        </div>
                        </div>
                    </div>
                    <div class="mb-2 card" style="border-radius: 16px;border: 1px solid #DBDBDB;background: rgba(244, 244, 244, 0.36);">
                        <div class="card-header" id="headingFour" style="background: none;">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseThree" style="color: #555;text-decoration: none;width: 100%;text-align: left;padding: 0;">
                            Fast and Reliable Shipping &nbsp;&nbsp;&nbsp;&nbsp; <span class="arrow-icon">&gt;</span>
                            </button>
                        </h5>
                        </div>
                        <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
                        <div class="card-body">
                            Fast and Reliable Shipping guarantees timely delivery with real-time tracking, trusted carriers, and careful handling—ensuring your products reach customers quickly and safely, every time.
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="{{asset('public/about2.png')}}" alt="" style="width: 100%">
            </div>
        </div>
    </div>

    <div class="container-fluid about-footer-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <h4 style="color: white;"><b>Our Commitment</b></h4>
                    <p style="color: #fff;text-align:justify;">SelfShop is dedicated to providing the best resources, tools, and products to help entrepreneurs succeed. We continuously innovate, adapting to market trends and customer needs, ensuring that our platform remains a valuable asset for online sellers.</p>
                </div>
                <div class="col-lg-2">
                    <div class="line"></div>
                </div>
                <div class="col-lg-5">
                    <h4 style="color: white;"><b>Connect With Us</b></h4>
                    <p style="color: #fff;text-align:justify;">Stay updated on our latest products, features, and business tips by following us on our social media platforms. For any inquiries or assistance, reach out to us at [Your Contact Information.</p>
                </div>
            </div>
            <div class="pt-4 mt-4 row">
                <div class="pt-4 col-lg-10">
                    <div class="card" style="border-radius: 16px;background: rgba(229, 0, 95, 0.53);backdrop-filter: blur(12px);">
                        <div class="p-3 row">
                            <div class="col-lg-3">
                            <img src="{{asset(App\Models\Basicinfo::first()->logo)}}" style="width: 210px;background: white;padding: 6px;border-radius: 6px;">
                            </div>
                            <div class="col-lg-9">
                            <p style="margin: 0;">Thank you for choosing SelfShop as your trusted eCommerce partner. We are excited to support your journey toward success.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <br>
    <br>
@endsection
