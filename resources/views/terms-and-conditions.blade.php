@extends('layouts.qr')

@section('title', 'Affordable & Powerful Dynamic QR Code Solutions for Your Business')
@section('description', 'Affordable & Powerful Dynamic QR Code Solutions for Your Business')
@section('keywords',
    'QR Code, Dynamic QR, Analytics, SaaS, Editable QR Codes, Trackable QR Codes, QR Code Subscription
    Plans, Custom QR Code Branding')

@section('body_class', 'class="terms-and-conditions-page"')

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
                    @if(!config('app.free_dynamic_qr_codes'))
                        <li><a href="{{route('welcome')}}#pricing">Pricing</a></li>
                    @endif
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

                            <h1 class="mb-4 text-center">Terms and Conditions</h1>

                            <div class="text-muted mb-4 text-center">
                                <p><strong>Last Updated:</strong> 05.08.2025</p>
                                <p><strong>Website:</strong> easy-qr-code.com</p>
                                <p><strong>Operator:</strong> Mayst Impact</p>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">1. Introduction</h2>
                                <p>Welcome to easy-qr-code.com ("we," "our," or "us"). These Terms and Conditions ("Terms")
                                    govern your use of our website, tools, and services (the "Service").</p>
                                <p>By using our Service, you agree to these Terms. If you do not agree, please do not use
                                    the Service.</p>
                                </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">2. Service Description</h2>
                                <p>We provide an online QR code generation tool that allows you to create dynamic QR codes
                                    with a 7-day validity period.</p>
                                <ul class="list-unstyled ms-3">
                                    <li class="mb-2">• You may extend the validity of an existing dynamic QR code for 1,
                                        3, 6, or 12 months by purchasing an extension package.</li>
                                    <li class="mb-2">• Features may include basic analytics, various QR code formats
                                        (e.g., website link, WiFi, contact, email, WhatsApp, SMS, phone call, calendar), and
                                        basic styling (e.g., colour and logo insertion).</li>
                                </ul>
                                <p>We do not currently offer advanced enterprise features, unless otherwise stated.</p>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">3. Eligibility</h2>
                                <p>You must be at least 18 years old or have the permission of a legal guardian to use our
                                    Service. By using the Service, you confirm that you meet this requirement.</p>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">4. Account & Usage</h2>
                                <ul class="list-unstyled ms-3">
                                    <li class="mb-2">• An account may be required for certain features. You are
                                        responsible for keeping your account secure.</li>
                                    <li class="mb-2">• You agree not to use our Service for unlawful purposes, including
                                        but not limited to phishing, spreading malware, or linking to illegal content.</li>
                                    <li class="mb-2">• We reserve the right to suspend or terminate your access if we
                                        believe your use violates these Terms.</li>
                                </ul>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">5. Payments & Pricing</h2>
                                <ul class="list-unstyled ms-3">
                                    <li class="mb-2">• All prices are listed in [Currency] and are subject to change at
                                        our discretion.</li>
                                    <li class="mb-2">• Payment is required before extending the validity of a QR code.
                                    </li>
                                    <li class="mb-2">• We do not provide refunds unless required by law, except in cases
                                        of service malfunction that cannot be resolved.</li>
                                </ul>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">6. QR Code Validity & Data</h2>
                                <ul class="list-unstyled ms-3">
                                    <li class="mb-2">• Free dynamic QR codes are valid for 7 days from creation.</li>
                                    <li class="mb-2">• Paid extensions add validity based on the selected plan.</li>
                                    <li class="mb-2">• After expiration, a QR code will become inactive and will not
                                        redirect to the linked content until extended.</li>
                                    <li class="mb-2">• You are responsible for the content linked via your QR codes.</li>
                                </ul>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">7. Analytics</h2>
                                <p>Our Service may provide basic usage statistics for dynamic QR codes.</p>
                                <p>These analytics are for informational purposes only and may not be 100% accurate.</p>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">8. Intellectual Property</h2>
                                <ul class="list-unstyled ms-3">
                                    <li class="mb-2">• All software, design, and branding on easy-qr-code.com are owned by
                                        us and protected by copyright laws.</li>
                                    <li class="mb-2">• You retain rights to the content you link to via our QR codes, but
                                        you grant us a non-exclusive license to store and display that content as necessary
                                        to operate the Service.</li>
                                </ul>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">9. Limitation of Liability</h2>
                                <p>We are not liable for any loss or damage resulting from:</p>
                                <ul class="list-unstyled ms-3">
                                    <li class="mb-2">• Service downtime or errors</li>
                                    <li class="mb-2">• Expired or deactivated QR codes</li>
                                    <li class="mb-2">• Third-party content linked via QR codes</li>
                                    <li class="mb-2">• Incorrect or incomplete analytics data</li>
                                </ul>
                                <p>Our maximum liability shall not exceed the total amount you paid for the Service in the
                                    past 12 months.</p>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">10. Changes to the Service & Terms</h2>
                                <p>We may update or modify these Terms at any time.</p>
                                <p>Any changes will be posted on this page with a new "Last Updated" date. Continued use of
                                    the Service after changes means you accept the updated Terms.</p>
                            </div>

                            <div class="mb-5">
                                <h2 class="h3 mb-3">11. Governing Law</h2>
                                <p>These Terms are governed by and construed in accordance with the laws of The Republic of North Macedonia.
                                </p>
                                <p>Any disputes shall be subject to the exclusive jurisdiction of the courts in The Republic of North Macedonia.</p>
                            </div>

                            <div>
                                <h2 class="h3 mb-3">12. Contact</h2>
                                <p>For any questions about these Terms, contact us at:</p>
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
