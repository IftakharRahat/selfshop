<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Dashboard - Admin</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  {{-- //csrf --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Favicons -->
  <link href="{{asset('public/admin/assets/')}}/img/adminfavicon.png" rel="icon">
  <link href="{{asset('public/admin/assets/')}}/img/adminapple-touch-icon.png" rel="apple-touch-icon">
  {{-- Css includes --}}
  @include('admin.linkincludes.css')

  @yield('subcss')

<style>
    .data-tables {
    overflow: scroll;
}
.toast.toast-success {
    background: #059669;
}
.toast.toast-error {
    background: #dc2626;
}
th.sorting_disabled {
    width: max-content !important;
    min-width: 60px;
}
th.sorting_disabled.customerInfo{
    width: 100px !important;
}
th.sorting_disabled.courierinfos{
    width: 70px !important;
}
th.sorting_disabled.customerInfo input.form-control {
    width: 120px;
}
th.sorting_disabled input.form-control {
    padding: 4px;
    font-size: 14px;
    min-height: calc(1.5em);
}
table.dataTable thead th, table.dataTable thead td {
    padding: 10px 8px  !important;
    border-bottom: 1px solid var(--admin-border, #e2e8f0);
}
</style>
</head>

<body>

<!---Header --->
    @include('admin.includes.header')

  <!-- ======= Sidebar ======= -->
    @include('admin.includes.sidebar')
  <!-- End Sidebar-->

  <!-- Start #main -->
    @yield('maincontent')
  <!-- End #main -->

  <!-- ======= Footer ======= -->
    @include('admin.includes.footer')
  <!-- End Footer -->


    {{-- //js include --}}
    @include('admin.linkincludes.js')

    @yield('subscript')

</body>

</html>
