@extends('layouts.qr')

@section('title', 'Refund Policy - EasyQRCode')
@section('description', 'Refund Policy for EasyQRCode - Learn about our refund process and when you may be eligible for a refund')
@section('keywords',
    'Refund Policy, Money Back Guarantee, Digital Services, QR Code Refunds, Payment Issues')

@section('body_class', 'class="refund-policy-page"')

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

                        <h1 class="mb-4 text-center">Refund Policy</h1>

                        <div class="text-muted mb-4 text-center">
                            <p><strong>Last Updated:</strong> 05.08.2025</p>
                            <p><strong>Website:</strong> easy-qr-code.com</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">1. Overview</h2>
                            <p>At easy-qr-code.com, we aim to provide a reliable, user-friendly QR code generation service.</p>
                            <p>This Refund Policy explains when you may be eligible for a refund and how to request one.</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">2. Digital Services & Refunds</h2>
                            <p>Our products are digital services that are available immediately after purchase.</p>
                            <p>This means we generally do not offer refunds once a dynamic QR code or extension has been activated, except in the following cases:</p>

                            <ul class="list-unstyled ms-3">
                                <li class="mb-3">
                                    <strong>• Technical Issues:</strong> A purchased QR code or extension did not work due to a fault on our side, and we were unable to resolve it within a reasonable time after you reported the issue.
                                </li>
                                <li class="mb-3">
                                    <strong>• Duplicate Payments:</strong> You were charged more than once for the same service due to a billing error.
                                </li>
                                <li class="mb-3">
                                    <strong>• Unauthorized Transactions:</strong> Your payment method was used without your consent, and you provide evidence of the unauthorized charge.
                                </li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">3. Non-Refundable Situations</h2>
                            <p>Refunds will not be provided in the following situations:</p>

                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• You changed your mind after purchase.</li>
                                <li class="mb-2">• You no longer need or want the QR code.</li>
                                <li class="mb-2">• The QR code expired before you extended it.</li>
                                <li class="mb-2">• You provided incorrect content for the QR code and did not request a correction before activation.</li>
                                <li class="mb-2">• You were unable to use the QR code due to reasons outside our control (e.g., device incompatibility, internet outages, scanner issues).</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">4. Requesting a Refund</h2>
                            <p>To request a refund, please contact our support team at <strong>mayst.impact@gmail.com</strong> within <strong>7 days</strong> of purchase, including:</p>

                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Your full name</li>
                                <li class="mb-2">• Email address used for purchase</li>
                                <li class="mb-2">• Order ID or payment receipt</li>
                                <li class="mb-2">• A description of the issue and any relevant screenshots</li>
                            </ul>

                            <p class="mt-3">We may request additional information to verify your claim.</p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h3 mb-3">5. Refund Processing</h2>
                            <p>If your refund request is approved:</p>

                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">• Refunds will be issued to the original payment method used for the purchase.</li>
                                <li class="mb-2">• Processing times may vary depending on your payment provider, but typically take 5–10 business days.</li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="h3 mb-3">6. Contact Us</h2>
                            <p>If you have any questions about this Refund Policy or need assistance, contact us at:</p>
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
