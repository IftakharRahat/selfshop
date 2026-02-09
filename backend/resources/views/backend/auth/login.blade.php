<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ env('APP_NAME') }} Login</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="{{ asset('backend/') }}/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ asset('backend/') }}/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="{{ asset('backend/') }}/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css"
        rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('backend/') }}/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('backend/') }}/css/style.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#myModal").modal('show');
        });
    </script>

    <style>
        .modal-content {
            background-image: url(../../../public/bgtech.gif);
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: #000;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 0.3rem;
            outline: 0;
            background-size: cover;
            background-repeat: no-repeat;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #000000;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgb(129, 129, 129);
            appearance: none;
            border-radius: 5px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            color: #000;
            background-color: #fff;
            border-color: #bababa;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgb(199 199 199 / 25%)
        }
    </style>

</head>

<body style="background:white;">
    <div class="" style="height:100vh;padding-top:60px;background: white;"> 
        <div class="container p-3">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-6 col-xl-5">
                    <div class="card bg-pattern">
    
                        <div class="card-body p-4">
    
                            <div class="text-center m-auto">
                                <a href="{{ url('/') }}"> <img src="{{ asset(preg_replace('#^public/#', '', \App\Models\Basicinfo::first()->logo ?? '')) }}"
                                        alt="logo" style="width:100%"> </a>
                                <p class="text-muted mb-4 mt-3">অ্যাডমিন প্যানেলে প্রবেশ করতে আপনার সঠিক ই-মেইল ও পাসওয়ার্ড ব্যাবহার করুন.</p>
                            </div> 
                            @if (\Session::has('error'))
                                <div class="alert alert-dark" style="color: red;background:black">
                                    {{ \Session::get('error') }}</div>
                            @endif 
                            <form action="{{ route('admin.login') }}" method="post">
                                @csrf
    
                                <div class="form-group mb-3">
                                    <label for="emailaddress">Email address</label>
                                    <input class="form-control " name="email" type="email" id="emailaddress" required="" autocomplete="email" autofocus="" value="" placeholder="Enter your email">
                                </div>
    
                                <div class="form-group mb-3">
                                    <label for="password">Password</label>
                                    <input id="password" type="password" class="form-control " name="password" required="" autocomplete="current-password" placeholder="Enter your password">
                                </div>
    
                                <div class="form-group mb-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="remember" class="custom-control-input" id="checkbox-signin">
                                        <label class="custom-control-label" for="checkbox-signin">Remember me</label>
                                    </div>
                                </div>
    
                                <div class="form-group mb-0 text-center">
                                    <button class="btn btn-primary btn-block" type="submit" style="width:100%;background-color: #0a296f;border-color: #0a296f;"> Log In </button>
                                </div>
    
                            </form>
    
    
    
                        </div> <!-- end card-body -->
                    </div>
                    <!-- end card -->
    
                </div> <!-- end col -->
            </div>
            <!-- end row -->
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('backend/') }}/lib/chart/chart.min.js"></script>
    <script src="{{ asset('backend/') }}/lib/easing/easing.min.js"></script>
    <script src="{{ asset('backend/') }}/lib/waypoints/waypoints.min.js"></script>
    <script src="{{ asset('backend/') }}/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="{{ asset('backend/') }}/lib/tempusdominus/js/moment.min.js"></script>
    <script src="{{ asset('backend/') }}/lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="{{ asset('backend/') }}/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="{{ asset('backend/') }}/js/main.js"></script>
</body>

</html>
