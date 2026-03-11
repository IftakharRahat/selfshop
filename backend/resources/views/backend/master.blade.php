<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title> @yield('title') </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    <meta name="csrf_token" content="{{ csrf_token() }}" />
    {{-- link include --}}
    @include('backend.partials.links.css')

    @yield('subcss')
    <style>
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


        i.fas.fa-trash-alt {
            color: red;
        }
        .form-select {
            background: white;
            color: black;
        }

        .table-dark {
            --bs-table-bg: #fff !important;
            --bs-table-striped-bg: #0d0d0d;
            --bs-table-striped-color: #fff;
            --bs-table-active-bg: #1a1a1a;
            --bs-table-active-color: #fff;
            --bs-table-hover-bg: #131313;
            --bs-table-hover-color: #fff;
            color: #000 !important;
            border-color: #1a1a1a;
        }

        .bg-primary {
            background-color: #0a296f !important;
        }

        .btn-dark {
            color: #fff !important;
            background-color: #0a296f !important;
            border-color: #0a296f !important;
        }

        .btn-danger {
            color: #fff !important;
            background-color: red !important;
            border-color: red !important;
        }

        a {
            color: #000000;
            text-decoration: none;
        }
        .btn-primary {
    color: #fff;
    background-color: #000000 !important;
    border-color: #000000 !important;
}
        .bg-info {
            background-color: #2E294E !important;
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #000 !important;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #000 !important;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #000 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            box-sizing: border-box;
            display: inline-block;
            min-width: 1.5em;
            padding: 0.5em 1em;
            margin-left: 2px;
            text-align: center;
            text-decoration: none !important;
            cursor: pointer;
            *cursor: hand;
            color: #000 !important;
            border: 1px solid #000;
            border-radius: 2px;
        }



        h1,
        .h1,
        h2,
        .h2,
        h3,
        .h3,
        h4,
        .h4,
        h5,
        .h5,
        h6,
        .h6 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-family: "Roboto", sans-serif;
            font-weight: 700;
            line-height: 1.2;
            color: #0c0c0c;
        }

        .bg-secondary {
            background-color: #fff !important;
        }

        .bg-dark {
            background-color: #fff !important;
        }

        label {
            color: #000 !important;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #000000 !important;
            background-color: #fff !important;
            background-clip: padding-box;
            border: 1px solid rgb(129, 129, 129);
            appearance: none;
            border-radius: 5px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            color: #000 !important;
            background-color: #fff !important;
            border-color: #bababa !important;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgb(199 199 199 / 25%)
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: red;
        }
        .data-tables {
            overflow: scroll;
        }

    </style>
    <style>
        /* Digit font — matches the Next.js storefront number style */
        .digit-font {
            font-family: "Poppins", "Roboto", sans-serif;
            font-weight: 800;
            letter-spacing: -0.025em;
            font-variant-numeric: tabular-nums;
            -webkit-font-feature-settings: "tnum";
            font-feature-settings: "tnum";
        }
    </style>
    <style>
        .card-box {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff !important;
            background-clip: border-box;
            border: none;
            border-radius: 5px;
            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
        }

        .text-dark {
            color: #000 !important;
        }

        .text-muted {
            color: #000 !important;
        }

        /* Toastr cleanup: remove repeated patterned success background and use consistent cards */
        #toast-container > .toast {
            width: 360px;
            max-width: calc(100vw - 24px);
            border-radius: 10px;
            border: 0;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.2);
            padding: 14px 16px;
            background-image: none !important;
            font-size: 15px;
            line-height: 1.45;
        }

        #toast-container > .toast-success {
            background-color: #16a34a !important;
            color: #ffffff !important;
        }

        #toast-container > .toast-error {
            background-color: #dc2626 !important;
            color: #ffffff !important;
        }

        #toast-container > .toast-info {
            background-color: #0284c7 !important;
            color: #ffffff !important;
        }

        #toast-container > .toast-warning {
            background-color: #d97706 !important;
            color: #ffffff !important;
        }

        #toast-container .toast-progress {
            opacity: 0.35;
        }
    </style>
</head>

<body>
    <div class="p-0 container-fluid">

        <!-- Sidebar Start -->
        @include('backend.partials.sidebar')
        <!-- Sidebar End -->


        <!-- Content Start -->

        <div class="content">

            <!-- Navbar Start -->
            @include('backend.partials.header')
            <!-- Navbar End -->

            <!-- Sale & Revenue Start  main content-->
            @yield('maincontent')
            <!-- Widgets End -->

            <!-- footer Start -->
            {{-- @include('backend.partials.footer') --}}
            <!-- footer End -->
        </div>
        <!-- Content End -->


    </div>

    {{-- js link includes --}}
    @include('backend.partials.links.js')

    @yield('subjs')

    <script>
        /**
         * Format a number using Bangladesh / Indian grouping (lakh, crore).
         * Example: formatBDT(35575)  → "35,575"
         *          formatBDT(124356) → "1,24,356"
         */
        function formatBDT(n, decimals) {
            if (typeof decimals === 'undefined') decimals = 0;
            var num = typeof n === 'string' ? parseFloat(n) || 0 : (n || 0);
            return new Intl.NumberFormat('en-IN', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        }
    </script>

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "newestOnTop": true,
            "preventDuplicates": true,
            "positionClass": "toast-top-right",
            "timeOut": "3500",
            "extendedTimeOut": "1200"
        };

        @if (Session::has('message'))
            toastr.success(@json(session('message')));
        @endif

        @if (Session::has('error'))
            toastr.error(@json(session('error')));
        @endif

        @if (Session::has('info'))
            toastr.info(@json(session('info')));
        @endif

        @if (Session::has('warning'))
            toastr.warning(@json(session('warning')));
        @endif
    </script>
</body>

</html>
