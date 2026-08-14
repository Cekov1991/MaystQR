<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', 'Create free static QR codes instantly, or dynamic QR codes you can edit and track.')">
    @hasSection('keywords')
        <meta name="keywords" content="@yield('keywords')">
    @endif
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')">
    @endif

    <link href="{{ asset('landing/assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('landing/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link href="{{ asset('css/site.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body>

    <div class="eq-shell">

        <header class="eq-header">
            <a href="{{ route('welcome') }}" class="eq-logo">
                <img src="{{ asset('images/easy-qr-logo-trim.png') }}" alt="{{ config('app.name') }}">
            </a>
            <nav class="eq-topnav">
                @auth
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="eq-btn eq-btn-outline eq-btn--nav">Dashboard</a>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" class="eq-btn eq-btn-outline eq-btn--nav">Log in</a>
                @endauth
            </nav>
        </header>

        <main class="eq-main">
            @yield('content')
        </main>

        <footer class="eq-footer">
            <span>© {{ date('Y') }} {{ config('app.name') }}</span>
            <nav>
                <a href="{{ url('/terms-and-conditions') }}">Terms &amp; Conditions</a>
                <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
                <a href="{{ url('/refund-policy') }}">Refund Policy</a>
                <a href="mailto:{{ config('site.support_email') }}">Contact</a>
            </nav>
            @if (config('site.credit.url'))
                <span class="eq-footer-credit">
                    Powered by <a href="{{ config('site.credit.url') }}">{{ config('site.credit.name') }}</a>
                </span>
            @endif
        </footer>

    </div>

    @stack('scripts')

    @include('partials.cookie-banner')
</body>

</html>
