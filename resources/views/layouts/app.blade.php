<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'TraceBility') }} - @yield('title', 'Dashboard')</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 4.6 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- DataTables CSS for Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Source Sans Pro', sans-serif;
        }

        .login-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-box,
        .register-box {
            width: 360px;
            margin: 7% auto;
        }

        .login-logo a,
        .register-logo a {
            color: #fff;
            font-size: 35px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* DataTables custom styling */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #333;
            padding: 10px 0;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 5px 5px;
            margin: 0 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007bff;
            color: white !important;
            border: 1px solid #007bff;
        }
    </style>

    @stack('css')
</head>

<!-- Untuk halaman login -->
@if (Request::is('login') || Request::is('register') || Request::is('password/*'))

    <body class="hold-transition login-page">
        <div class="login-box">
            <div class="login-logo mb-4">
                <a href="{{ url('/') }}"><b>Admin</b>LTE</a>
            </div>
            @yield('content')
        </div>
    @else
        <!-- Untuk halaman dashboard/beranda -->

        <body class="hold-transition sidebar-mini layout-fixed">
            <div class="wrapper">
                <!-- Navbar -->
                @include('layouts.navbar')

                <!-- Sidebar -->
                @include('layouts.sidebar')

                <!-- Content Wrapper -->
                <div class="content-wrapper">
                    <!-- Content Header -->
                    <div class="content-header">
                        <div class="container-fluid">
                            <div class="row mt-3 mb-2">
                                <div class="col-sm-6">
                                    <h5 class="m-0">@yield('title', 'Dashboard')</h5>
                                </div>
                                <div class="col-sm-6">
                                    <ol class="breadcrumb float-sm-right">
                                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                        <li class="breadcrumb-item active">@yield('title', 'Dashboard')</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <section class="content">
                        <div class="container-fluid">
                            @yield('content')
                        </div>
                    </section>
                </div>

                <!-- Footer -->
                <footer class="main-footer">
                    <strong>Copyright &copy; {{ date('Y') }} <a
                            href="{{ url('/') }}">{{ config('app.name', 'Laravel') }}</a>.</strong>
                    All rights reserved.
                    <div class="float-right d-none d-sm-inline-block">
                        <b>Version</b> 1.0.0
                    </div>
                </footer>
            </div>
@endif

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 4.6 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables Core JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

<!-- DataTables Extensions (Optional) -->
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- Global DataTables Initialization -->
<script>
    $(document).ready(function() {
        console.log('AdminLTE loaded successfully');

        // Enable tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Enable popovers
        $('[data-toggle="popover"]').popover();

        // Auto-initialize DataTables on tables with class "datatable"
        if ($.fn.DataTable) {
            console.log('DataTables is loaded:', $.fn.DataTable);
            $('.datatable').each(function() {
                var table = $(this);
                if (!table.hasClass('dataTable')) {
                    table.DataTable({
                        responsive: true,
                        pageLength: 25,
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "Search...",
                            lengthMenu: "_MENU_ records per page",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries",
                            infoEmpty: "Showing 0 to 0 of 0 entries",
                            infoFiltered: "(filtered from _MAX_ total entries)",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            }
                        }
                    });
                }
            });
        } else {
            console.error('DataTables is NOT loaded. Check script order.');
        }
    });
</script>

@stack('scripts')
</body>

</html>
