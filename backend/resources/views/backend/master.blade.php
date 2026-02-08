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
    </style>
</head>

<body>
    <!-- Navbar Start -->
    @include('backend.partials.header')
    <!-- Navbar End -->
    <div class="p-0 container-fluid">

        <!-- Sidebar Start -->
        @include('backend.partials.sidebar')
        <!-- Sidebar End -->


        <!-- Content Start -->

        <div class="content">

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
        @if (Session::has('message'))
            toastr.options = {
                "closeButton": true,
                "progressBar": true
            }
            toastr.success("{{ session('message') }}");
        @endif

        @if (Session::has('error'))
            toastr.options = {
                "closeButton": true,
                "progressBar": true
            }
            toastr.error("{{ session('error') }}");
        @endif

        @if (Session::has('info'))
            toastr.options = {
                "closeButton": true,
                "progressBar": true
            }
            toastr.info("{{ session('info') }}");
        @endif

        @if (Session::has('warning'))
            toastr.options = {
                "closeButton": true,
                "progressBar": true
            }
            toastr.warning("{{ session('warning') }}");
        @endif
    </script>
</body>

</html>
