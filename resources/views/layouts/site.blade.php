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

    {{--
        Bunny Fonts, not Google Fonts. Hotlinking fonts.googleapis.com sent every
        visitor's IP to Google — on the homepage, the pricing page and the privacy
        policy itself — with Google named nowhere as a processor. Bunny is
        EU-operated, sets no cookies and logs no IPs, and the rest of the app
        (layouts.app, layouts.guest) already uses it.
    --}}
    <link href="https://fonts.bunny.net" rel="preconnect">
    <link href="https://fonts.bunny.net/css?family=manrope:500,700,800|inter:400,500,600&display=swap" rel="stylesheet">

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
                <a href="{{ route('pricing') }}" class="eq-navlink">Pricing</a>
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
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="{{ url('/terms-and-conditions') }}">Terms &amp; Conditions</a>
                <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
                <a href="{{ url('/refund-policy') }}">Refund Policy</a>
                <a href="{{ route('report.create') }}">Report a QR code</a>
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
