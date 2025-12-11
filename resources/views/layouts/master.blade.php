<!DOCTYPE html>
<html>

<head>
  <title>@yield('title')</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
  <div class="container mt-3">
    @yield('content')
  </div>
  @stack('scripts')
</body>

</html>