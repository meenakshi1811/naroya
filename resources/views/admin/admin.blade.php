<!DOCTYPE html>
<html>
@include('admin.head')
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <!-- Include your Navbar -->
        @include('admin.navbar')
        <!-- Include your Sidebar -->
        @include('admin.sidebar')

        <!-- Content Wrapper -->
        <div class="app-main">
            @yield('content')
        </div>

        <!-- Include your Footer -->
        @include('admin.footer')
    </div>

    <script src="{{ asset('js/adminlte.min.js') }}"></script>
    <!-- Include other JS files as needed -->
</body>
</html>
