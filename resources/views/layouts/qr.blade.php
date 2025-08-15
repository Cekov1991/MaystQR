<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield('title', config('app.name') . ' - QR Code Management')</title>
    <meta name="description" content="@yield('description', 'Professional QR Code Solutions')">
    <meta name="keywords" content="@yield('keywords', 'QR Code, Dynamic QR, Analytics, SaaS')">

    <!-- Favicons -->
    <link href="{{ asset('landing/assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('landing/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('landing/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('landing/assets/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="{{ asset('landing/assets/css/main.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body @yield('body_class')>
    @yield('header')

    <main class="main">
        @yield('content')
    </main>

    @hasSection('footer')
        @yield('footer')
    @else
        <footer id="footer" class="footer mt-5">
            <div class="container footer-top">
                <div class="row gy-4">
                    <!-- Company Information -->
                    <div class="col-lg-4 col-md-6 footer-about">
                        <a href="/" class="logo d-flex align-items-center">
                            <span class="sitename">{{ config('app.name') }}</span>
                        </a>
                        <div class="footer-contact pt-3">
                            <p>Create dynamic QR codes with powerful analytics and easy management. Perfect for businesses of all sizes.</p>
                            <p><strong>Email:</strong> <span>mayst.impact@gmail.com</span></p>
                            <p><strong>Address:</strong> <span>Vladimir Komarov 25/4-16, Skopje, North Macedonia</span></p>
                        </div>
                        {{-- <div class="social-links d-flex mt-3">
                            <a href="#" class="d-flex align-items-center justify-content-center"><i class="bi bi-twitter-x"></i></a>
                            <a href="#" class="d-flex align-items-center justify-content-center"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="d-flex align-items-center justify-content-center"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="d-flex align-items-center justify-content-center"><i class="bi bi-linkedin"></i></a>
                        </div> --}}
                    </div>

                    <!-- Quick Links -->
                    <div class="col-lg-2 col-md-3 footer-links">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="{{route('welcome')}}">Home</a></li>
                            <li><a href="{{route('welcome')}}/#about">About</a></li>
                            <li><a href="{{route('welcome')}}/#features">Features</a></li>
                            @if(!config('app.free_dynamic_qr_codes'))
                                <li><a href="{{route('welcome')}}/#pricing">Pricing</a></li>
                            @endif
                            <li><a href="{{route('welcome')}}/#faq">FAQ</a></li>
                        </ul>
                    </div>

                    <!-- Services -->
                    <div class="col-lg-2 col-md-3 footer-links">
                        <h4>Services</h4>
                        <ul>
                            <li><a href="{{ route('filament.public.resources.qrcodes.create') }}">Create QR Code</a></li>
                            <li><a href="{{ route('filament.admin.pages.dashboard') }}">Dashboard</a></li>
                            <li><a href="#">QR Analytics</a></li>
                        </ul>
                    </div>

                    <!-- Legal & Support -->
                    <div class="col-lg-2 col-md-3 footer-links">
                        <h4>Legal & Support</h4>
                        <ul>
                            <li><a href="{{ url('/terms-and-conditions') }}">Terms & Conditions</a></li>
                            <li><a href="{{ url('/privacy-policy') }}">Privacy Policy</a></li>
                            {{-- <li><a href="{{ url('/refund-policy') }}">Refund Policy</a></li> --}}
                            <li><a href="mailto:mayst.impact@gmail.com">Contact Support</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            {{-- @dd(request()->cookie()) --}}

            <div class="container copyright text-center mt-4">
                <p>© <span>Copyright</span> <strong class="px-1 sitename">{{ config('app.name') }}</strong> <span>All Rights Reserved</span></p>
                <div class="credits">
                    <small class="text-muted">Powered by <a href="https://maystimpact.mk">Mayst Impact</a></small>
                </div>
            </div>

            <!-- Scroll Top -->
            <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
        </footer>
    @endif

    <!-- Vendor JS Files -->
    <script src="{{ asset('landing/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('landing/assets/vendor/php-email-form/validate.js') }}"></script>
    <script src="{{ asset('landing/assets/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('landing/assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('landing/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('landing/assets/vendor/purecounter/purecounter_vanilla.js') }}"></script>

    <!-- Main JS File -->
    <script src="{{ asset('landing/assets/js/main.js') }}"></script>

    @stack('scripts')

    @if (!request()->cookie('cookie_consent'))
        <div id="cookie-banner"
            style="position: fixed; bottom: 0; left: 0; right: 0; background: #222; color: #fff; padding: 15px; font-size: 14px; display: flex; justify-content: space-between; align-items: center; z-index: 9999;">
            <span>
                We use essential cookies for site functionality and Google Analytics to improve your experience.
                See our <a href="{{ url('/privacy-policy') }}" style="color: #4da3ff;">Privacy Policy</a>.
            </span>
            <div>
                <a href="{{route('cookies.accept')}}"
                    style="background: #4caf50; color: white; border: none; padding: 8px 12px; margin-right: 5px; cursor: pointer;">Accept</a>
                <a href="{{route('cookies.reject')}}"
                    style="background: #f44336; color: white; border: none; padding: 8px 12px; cursor: pointer;">Decline</a>
            </div>
        </div>
    @endif
</body>

</html>
