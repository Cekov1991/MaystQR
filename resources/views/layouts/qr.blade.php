<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield('title', 'MaystQR - QR Code Management')</title>
    <meta name="description" content="@yield('description', 'Professional QR Code Solutions')">

    <!-- Favicons -->
    <link href="{{ asset('landing/assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('landing/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('landing/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="{{ asset('landing/assets/css/main.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body>
    <header class="header d-flex align-items-center fixed-top">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="{{ url('/') }}" class="logo d-flex align-items-center">
                <h1 class="sitename">MaystQR</h1>
            </a>

            <nav class="navmenu">
                <ul>
                    @auth
                        <li><a href="{{ route('filament.admin.pages.dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('filament.admin.resources.qr-codes.index') }}">My QR Codes</a></li>
                    @else
                        <li><a href="{{ route('login') }}">Login</a></li>
                        <li><a href="{{ route('register') }}">Register</a></li>
                    @endauth
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; {{ date('Y') }} MaystQR. All rights reserved.</p>
        </div>
    </footer>

    <!-- Vendor JS Files -->
    <script src="{{ asset('landing/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    @stack('scripts')
</body>
</html>