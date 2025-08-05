@extends('layouts.qr')

@section('title', 'Privacy Policy - EasyQRCode')
@section('description', 'Privacy Policy for EasyQRCode - Learn how we collect, use, and protect your personal information')
@section('keywords',
    'Privacy Policy, Data Protection, GDPR, Personal Information, Cookies, Analytics, QR Code Privacy')

@section('body_class', 'class="privacy-policy-page"')

@section('header')
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div
            class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

            <a href="{{route('welcome')}}" class="logo d-flex align-items-center me-auto me-xl-0">
                <!-- Uncomment the line below if you also wish to use an image logo -->
                <!-- <img src="assets/img/logo.png" alt=""> -->
                <h1 class="sitename">{{ config('app.name') }}</h1>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="{{ route('filament.admin.pages.dashboard') }}" class="active">Dashboard</a></li>
                    <li><a href="{{route('welcome')}}#about">About</a></li>
                    <li><a href="{{route('welcome')}}#features">Features</a></li>
                    <li><a href="{{route('welcome')}}#pricing">Pricing</a></li>
                    <li><a href="{{route('welcome')}}#faq">FAQ</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a class="btn-getstarted"
                href="{{ Auth::check() ? route('filament.admin.pages.dashboard') : route('filament.public.resources.qrcodes.create') }}">Get
                Started</a>

        </div>
    </header>
@endsection

@section('content')
<main class="main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="terms-content mt-5 py-5 card">
                    <div class="card-body p-5">

                        <h1 class="mb-4 text-center">Privacy Policy</h1>

                        <div class="text-muted mb-4 text-center">
                            <p><strong>Last Updated:</strong> 05.08.2025</p>
                            <p><strong>Website:</strong> easy-qr-code.com</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">1. Introduction</h2>
                            <p>Welcome to easy-qr-code.com ("we," "our," or "us").</p>
                            <p>We respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, and protect your data when you use our website and services.</p>
                            <p>By using our Service, you agree to the practices described in this Policy.</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">2. Information We Collect</h2>

                            <h3 class="h5 mb-3">a) Information You Provide</h3>
                            <p>When registering for our platform, we collect:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Name (first and/or last)</li>
                                <li class="mb-2">• Email address</li>
                                <li class="mb-2">• Password (encrypted; we cannot see your actual password)</li>
                            </ul>
                            <p>You may also provide optional information, such as custom QR code content and uploaded logos.</p>

                            <h3 class="h5 mb-3 mt-4">b) Information Collected Automatically</h3>
                            <p>When you use our website, we automatically collect:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• IP address</li>
                                <li class="mb-2">• Browser type & version</li>
                                <li class="mb-2">• Pages visited and time spent</li>
                                <li class="mb-2">• Device information</li>
                                <li class="mb-2">• Basic QR code scan statistics (for dynamic QR codes)</li>
                            </ul>

                            <h3 class="h5 mb-3 mt-4">c) Cookies</h3>
                            <p>We use cookies for:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• <strong>Essential functions:</strong> Laravel's default cookies for session management and security (CSRF protection)</li>
                                <li class="mb-2">• <strong>Analytics:</strong> Google Analytics cookies to understand how visitors use our site and improve our Service</li>
                                <li class="mb-2">• <strong>Preferences:</strong> To remember your settings where applicable</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">3. Use of Google Analytics</h2>
                            <p>We use Google Analytics, a web analytics service provided by Google LLC ("Google").</p>
                            <p>Google Analytics collects data such as:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Pages visited, time spent on pages</li>
                                <li class="mb-2">• Browser and device information</li>
                                <li class="mb-2">• Geographic location (approximate)</li>
                                <li class="mb-2">• IP address (anonymized where required by law)</li>
                            </ul>
                            <p>Google uses this information to evaluate your use of our website and provide reports. Data may be stored on Google servers in the United States.</p>
                            <p>You can opt out of Google Analytics tracking by installing the Google Analytics Opt-out Browser Add-on.</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">4. How We Use Your Information</h2>
                            <p>We use your information to:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Provide and manage your account</li>
                                <li class="mb-2">• Generate and maintain your QR codes</li>
                                <li class="mb-2">• Process payments and extensions</li>
                                <li class="mb-2">• Send important service updates</li>
                                <li class="mb-2">• Improve our website and services</li>
                                <li class="mb-2">• Respond to customer support requests</li>
                                <li class="mb-2">• Analyse usage trends (via Google Analytics)</li>
                            </ul>
                            <p><strong>We do not sell your personal data.</strong></p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">5. Email Communication</h2>
                            <p>By creating an account, you agree to receive:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Transactional emails (e.g., account confirmation, password reset, service updates)</li>
                                <li class="mb-2">• Optional product updates or promotions (only if you opt-in — you can unsubscribe anytime)</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">6. Data Retention</h2>
                            <p>We keep your data for as long as your account is active.</p>
                            <p>If you delete your account, we remove your personal details from our active systems, except where required by law.</p>
                            <p>Google Analytics data is stored according to Google's retention settings (we currently use 26 months).</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">7. Sharing Your Information</h2>
                            <p>We may share your data only with:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Service providers (e.g., hosting, payment processors, analytics) to operate the Service</li>
                                <li class="mb-2">• Google (via Google Analytics)</li>
                                <li class="mb-2">• Legal authorities if required by law</li>
                            </ul>
                            <p><strong>We do not share your information with advertisers.</strong></p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">8. Your Rights</h2>
                            <p>Depending on your location (e.g., under GDPR or CCPA), you have the right to:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Access the data we hold about you</li>
                                <li class="mb-2">• Request correction or deletion of your data</li>
                                <li class="mb-2">• Restrict or object to certain processing</li>
                                <li class="mb-2">• Request a copy of your data in a portable format</li>
                                <li class="mb-2">• Withdraw consent for marketing emails or analytics tracking</li>
                            </ul>
                            <p>To exercise these rights, contact us at mayst.impact@gmail.com.</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">9. Data Security</h2>
                            <p>We take reasonable measures to protect your information, including:</p>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Encrypted passwords</li>
                                <li class="mb-2">• Secure HTTPS connection</li>
                                <li class="mb-2">• Restricted database access</li>
                            </ul>
                            <p>However, no online service can be 100% secure.</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">10. International Users</h2>
                            <p>If you access our Service from outside The Republic of North Macedonia, your data will be stored and processed in The Republic of North Macedonia, where data protection laws may differ.</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">11. Changes to This Policy</h2>
                            <p>We may update this Privacy Policy occasionally.</p>
                            <p>If we make significant changes, we will notify you by email or through the website before the changes take effect.</p>
                        </div>

                        <div>
                            <h2 class="h3 mb-3">12. Contact Us</h2>
                            <p>If you have any questions about this Privacy Policy, contact us at:</p>
                            <div class="contact-info ms-3">
                                <p><strong>Email:</strong> mayst.impact@gmail.com</p>
                                <p><strong>Address:</strong> Vladimir Komarov 25/4-16</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection