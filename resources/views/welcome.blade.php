@extends('layouts.qr')

@section('title', 'Affordable & Powerful Dynamic QR Code Solutions for Your Business')
@section('description', 'Affordable & Powerful Dynamic QR Code Solutions for Your Business')
@section('keywords', 'QR Code, Dynamic QR, Analytics, SaaS, Editable QR Codes, Trackable QR Codes, QR Code Subscription Plans, Custom QR Code Branding')

@section('body_class', 'class="index-page"')

@section('header')
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

        <a href="/" class="logo d-flex align-items-center me-auto me-xl-0">
            <!-- Uncomment the line below if you also wish to use an image logo -->
            <!-- <img src="assets/img/logo.png" alt=""> -->
            <h1 class="sitename">{{ config('app.name') }}</h1>
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="{{route('filament.admin.pages.dashboard')}}" class="active">Dashboard</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

        <a class="btn-getstarted" href="{{Auth::check() ? route('filament.admin.pages.dashboard') : route('filament.public.resources.qrcodes.create')}}">Get Started</a>

    </div>
</header>
@endsection

@section('content')
    <!-- Hero Section -->
    <section id="hero" class="hero section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content" data-aos="fade-up" data-aos-delay="200">
                        <div class="company-badge mb-4">
                            <i class="bi bi-gear-fill me-2"></i>
                            Dynamic QR Code Generator: Affordable & Powerful Solutions
                        </div>

                        <h1 class="mb-4">
                            Track, Analyze,<br>
                            and Update Your QR Codes in Real-Time <br>
                            <span class="accent-text">Without Breaking the Bank</span>
                        </h1>

                        <p class="mb-4 mb-md-5">
                            Simplify customer engagement, monitor scan analytics, and update your QR code content anytime with our AWS-powered, cost-effective SaaS platform.
                        </p>

                        <div class="hero-buttons">
                            <a href="{{Auth::check() ? route('filament.admin.pages.dashboard') : route('filament.public.resources.qrcodes.create')}}" class="btn btn-primary me-0 me-sm-2 mx-1">Get Started</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="hero-image" data-aos="zoom-out" data-aos-delay="300">
                        <img src="{{ asset('landing/assets/img/illustration-1.webp') }}" alt="Hero Image" class="img-fluid">

                        <div class="customers-badge">
                            <div class="customer-avatars">
                                <img src="{{ asset('landing/assets/img/avatar-1.webp') }}" alt="Customer 1" class="avatar">
                                <img src="{{ asset('landing/assets/img/avatar-2.webp') }}" alt="Customer 2" class="avatar">
                                <img src="{{ asset('landing/assets/img/avatar-3.webp') }}" alt="Customer 3" class="avatar">
                                <img src="{{ asset('landing/assets/img/avatar-4.webp') }}" alt="Customer 4" class="avatar">
                                <img src="{{ asset('landing/assets/img/avatar-5.webp') }}" alt="Customer 5" class="avatar">
                                <span class="avatar more">12+</span>
                            </div>
                            <p class="mb-0 mt-2">12,000+ happy customers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row gy-4 align-items-center justify-content-between">
                <div class="col-xl-5" data-aos="fade-up" data-aos-delay="200">
                    <span class="about-meta">MORE ABOUT US</span>
                    <h2 class="about-title">Empowering Businesses with Mayst QR Code Solutions</h2>
                    <p class="about-description">Our platform helps small businesses, marketers, and event organizers create and manage dynamic QR codes effortlessly. With powerful analytics, seamless integrations, and unbeatable pricing, we're redefining how businesses connect with their audiences.</p>

                    <div class="row feature-list-wrapper">
                        <div class="col-md-6">
                            <ul class="feature-list">
                                <li><i class="bi bi-check-circle-fill"></i> Easy-to-Use Dashboard</li>
                                <li><i class="bi bi-check-circle-fill"></i> Real-Time Analytics & Insights</li>
                                <li><i class="bi bi-check-circle-fill"></i> Cost-Effective Plans</li>
                            </ul>
                        </div>
                    </div>

                    <div class="info-wrapper">
                        <div class="row gy-4">
                            <div class="col-lg-5">
                                {{-- Profile section if needed --}}
                            </div>
                            <div class="col-lg-7">
                                {{-- Contact info if needed --}}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="image-wrapper">
                        <div class="images position-relative" data-aos="zoom-out" data-aos-delay="400">
                            <img src="{{ asset('landing/assets/img/about-5.webp') }}" alt="Business Meeting" class="img-fluid main-image rounded-4">
                            <img src="{{ asset('landing/assets/img/about-2.webp') }}" alt="Team Discussion" class="img-fluid small-image rounded-4">
                        </div>
                        <div class="experience-badge floating">
                            <h3>15+ <span>Years</span></h3>
                            <p>Of experience in business service</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section><!-- /About Section -->

    <!-- Features Cards Section -->
    <section id="features" class="features-cards section">
        <div class="container">
            <div class="row gy-4">
                <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="100">
                    <div class="feature-box orange">
                        <i class="bi bi-award"></i>
                        <h4>Editable QR Codes</h4>
                        <p>Update your QR code destinations anytime without reprinting.</p>
                    </div>
                </div><!-- End Feature Box-->

                <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="200">
                    <div class="feature-box blue">
                        <i class="bi bi-patch-check"></i>
                        <h4>Powerful Analytics</h4>
                        <p>Get insights on scans, locations, devices, and engagement.</p>
                    </div>
                </div><!-- End Feature Box-->

                <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="300">
                    <div class="feature-box green">
                        <i class="bi bi-sunrise"></i>
                        <h4>Custom Branding</h4>
                        <p>Create QR codes that align with your brand identity.</p>
                    </div>
                </div><!-- End Feature Box-->

                <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="400">
                    <div class="feature-box red">
                        <i class="bi bi-shield-check"></i>
                        <h4>Cost-Effective Plans</h4>
                        <p>Affordable solutions for individuals, SMBs, and enterprises.</p>
                    </div>
                </div><!-- End Feature Box-->
            </div>
        </div>
    </section><!-- /Features Cards Section -->

    <!-- Pricing Section -->
    <section id="pricing" class="pricing section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <!-- Section Header -->
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mb-4">Simple, Transparent Pricing</h2>
                    <p class="lead mb-5">
                        Start for free and scale as you grow. Our flexible pricing model ensures you only pay for what you need.
                    </p>
                </div>
            </div>

            <!-- Business Model Explanation -->
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="text-primary mb-2">💡 How Our Pricing Works</h4>
                                    <p class="mb-2">
                                        <strong>Static QR Codes:</strong> Create unlimited static QR codes completely free - forever! Perfect for simple redirects, contact info, and basic use cases.
                                    </p>
                                    <p class="mb-0">
                                        <strong>Dynamic QR Codes:</strong> Each dynamic QR code comes with a <span class="text-success fw-bold">{{ config('app.qr_code_trial_days') }} days free trial</span>. After the trial, simply purchase an affordable extension package to keep your QR code active. You only pay for the dynamic features you actually use!
                                    </p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="text-success">
                                        <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0"><small>Pay only for what you use</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="row justify-content-center">
                <!-- Dynamic Extension Packages -->
                @foreach($packages as $package)
                    <div class="col-lg-4 mt-4" data-aos="fade-up" data-aos-delay="{{ 200 + ($loop->index * 100) }}">
                        <div class="pricing-card {{ $package->id == 2 ? 'popular' : '' }}">
                            @if($package->id == 2)
                                <div class="popular-badge">Most Popular</div>
                            @endif
                            <h3>{{ $package->name }} Extension</h3>
                            <div class="price">
                                <span class="currency">$</span>
                                <span class="amount">{{ $package->price }}</span>
                                <span class="period">/ QR code</span>
                            </div>

                            <h4>Extension Includes:</h4>
                            <ul class="features-list">
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    {{ $package->duration_text }} extension per QR code
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Unlimited scans & updates
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Real-time analytics
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Custom branding options
                                </li>
                            </ul>

                            <a href="{{ route('filament.public.resources.qrcodes.create') }}" class="btn {{ $package->id == 2 ? 'btn-light' : 'btn-primary' }}">
                                Get Started
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Value Proposition -->
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8 text-center">
                    <div class="value-proposition">
                        <h4 class="text-primary mb-3">Why This Model Benefits You</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-piggy-bank text-success mb-2" style="font-size: 2rem;"></i>
                                <h6>Cost Effective</h6>
                                <p class="small">Only pay for advanced features when you need them</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-clock text-success mb-2" style="font-size: 2rem;"></i>
                                <h6>Risk-Free Trial</h6>
                                <p class="small">{{ config('app.qr_code_trial_days') }} days to test all dynamic features before committing</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-graph-up text-success mb-2" style="font-size: 2rem;"></i>
                                <h6>Scale as You Grow</h6>
                                <p class="small">Start small and add more dynamic QR codes as needed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section><!-- /Pricing Section -->

    <!-- Call To Action Section -->
    <section id="call-to-action" class="call-to-action section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row content justify-content-center align-items-center position-relative">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-4 mb-4">Start Growing with Mayst QR Codes Today!</h2>
                    <a href="{{Auth::check() ? route('filament.admin.pages.dashboard') : route('filament.public.resources.qrcodes.create')}}" class="btn btn-cta">Get Started</a>
                </div>

                <!-- Abstract Background Elements -->
                <div class="shape shape-1">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path d="M47.1,-57.1C59.9,-45.6,68.5,-28.9,71.4,-10.9C74.2,7.1,71.3,26.3,61.5,41.1C51.7,55.9,35,66.2,16.9,69.2C-1.3,72.2,-21,67.8,-36.9,57.9C-52.8,48,-64.9,32.6,-69.1,15.1C-73.3,-2.4,-69.5,-22,-59.4,-37.1C-49.3,-52.2,-32.8,-62.9,-15.7,-64.9C1.5,-67,34.3,-68.5,47.1,-57.1Z" transform="translate(100 100)"></path>
                    </svg>
                </div>

                <div class="shape shape-2">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path d="M41.3,-49.1C54.4,-39.3,66.6,-27.2,71.1,-12.1C75.6,3,72.4,20.9,63.3,34.4C54.2,47.9,39.2,56.9,23.2,62.3C7.1,67.7,-10,69.4,-24.8,64.1C-39.7,58.8,-52.3,46.5,-60.1,31.5C-67.9,16.4,-70.9,-1.4,-66.3,-16.6C-61.8,-31.8,-49.7,-44.3,-36.3,-54C-22.9,-63.7,-8.2,-70.6,3.6,-75.1C15.4,-79.6,28.2,-58.9,41.3,-49.1Z" transform="translate(100 100)"></path>
                    </svg>
                </div>

                <!-- Dot Pattern Groups -->
                <div class="dots dots-1">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <pattern id="dot-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="2" fill="currentColor"></circle>
                        </pattern>
                        <rect width="100" height="100" fill="url(#dot-pattern)"></rect>
                    </svg>
                </div>

                <div class="dots dots-2">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <pattern id="dot-pattern-2" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="2" fill="currentColor"></circle>
                        </pattern>
                        <rect width="100" height="100" fill="url(#dot-pattern-2)"></rect>
                    </svg>
                </div>

                <div class="shape shape-3">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path d="M43.3,-57.1C57.4,-46.5,71.1,-32.6,75.3,-16.2C79.5,0.2,74.2,19.1,65.1,35.3C56,51.5,43.1,65,27.4,71.7C11.7,78.4,-6.8,78.3,-23.9,72.4C-41,66.5,-56.7,54.8,-65.4,39.2C-74.1,23.6,-75.8,4,-71.7,-13.2C-67.6,-30.4,-57.7,-45.2,-44.3,-56.1C-30.9,-67,-15.5,-74,0.7,-74.9C16.8,-75.8,33.7,-70.7,43.3,-57.1Z" transform="translate(100 100)"></path>
                    </svg>
                </div>
            </div>
        </div>
    </section><!-- /Call To Action Section -->

    <!-- Faq Section -->
    <section class="faq-9 faq section light-background" id="faq">
        <div class="container">
            <div class="row">
                <div class="col-lg-5" data-aos="fade-up">
                    <h2 class="faq-title">Have a question? Check out the FAQ</h2>
                    <p class="faq-description">We're here to help! If you have any questions, please don't hesitate to reach out to us.</p>
                    <div class="faq-arrow d-none d-lg-block" data-aos="fade-up" data-aos-delay="200">
                        <svg class="faq-arrow" width="200" height="211" viewBox="0 0 200 211" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M198.804 194.488C189.279 189.596 179.529 185.52 169.407 182.07L169.384 182.049C169.227 181.994 169.07 181.939 168.912 181.884C166.669 181.139 165.906 184.546 167.669 185.615C174.053 189.473 182.761 191.837 189.146 195.695C156.603 195.912 119.781 196.591 91.266 179.049C62.5221 161.368 48.1094 130.695 56.934 98.891C84.5539 98.7247 112.556 84.0176 129.508 62.667C136.396 53.9724 146.193 35.1448 129.773 30.2717C114.292 25.6624 93.7109 41.8875 83.1971 51.3147C70.1109 63.039 59.63 78.433 54.2039 95.0087C52.1221 94.9842 50.0776 94.8683 48.0703 94.6608C30.1803 92.8027 11.2197 83.6338 5.44902 65.1074C-1.88449 41.5699 14.4994 19.0183 27.9202 1.56641C28.6411 0.625793 27.2862 -0.561638 26.5419 0.358501C13.4588 16.4098 -0.221091 34.5242 0.896608 56.5659C1.8218 74.6941 14.221 87.9401 30.4121 94.2058C37.7076 97.0203 45.3454 98.5003 53.0334 98.8449C47.8679 117.532 49.2961 137.487 60.7729 155.283C87.7615 197.081 139.616 201.147 184.786 201.155L174.332 206.827C172.119 208.033 174.345 211.287 176.537 210.105C182.06 207.125 187.582 204.122 193.084 201.144C193.346 201.147 195.161 199.887 195.423 199.868C197.08 198.548 193.084 201.144 195.528 199.81C196.688 199.192 197.846 198.552 199.006 197.935C200.397 197.167 200.007 195.087 198.804 194.488ZM60.8213 88.0427C67.6894 72.648 78.8538 59.1566 92.1207 49.0388C98.8475 43.9065 106.334 39.2953 114.188 36.1439C117.295 34.8947 120.798 33.6609 124.168 33.635C134.365 33.5511 136.354 42.9911 132.638 51.031C120.47 77.4222 86.8639 93.9837 58.0983 94.9666C58.8971 92.6666 59.783 90.3603 60.8213 88.0427Z" fill="currentColor"></path>
                        </svg>
                    </div>
                </div>

                <div class="col-lg-7" data-aos="fade-up" data-aos-delay="300">
                    <div class="faq-container">
                        <div class="faq-item faq-active">
                            <h3>What are the benefits of using dynamic QR codes over static ones?</h3>
                            <div class="faq-content">
                                <p>Dynamic QR codes allow you to update the content without reprinting, provide detailed scan analytics, and custom branding.</p>
                            </div>
                            <i class="faq-toggle bi bi-chevron-right"></i>
                        </div><!-- End Faq item-->

                        <div class="faq-item">
                            <h3>Are there limits to the number of scans per month?</h3>
                            <div class="faq-content">
                                <p>No, unlimited scans.</p>
                            </div>
                            <i class="faq-toggle bi bi-chevron-right"></i>
                        </div><!-- End Faq item-->

                        <div class="faq-item">
                            <h3>What kind of analytics do I get with dynamic QR codes?</h3>
                            <div class="faq-content">
                                <p>You'll have access to:
                                    <strong>Total and unique scans</strong>,
                                    <strong>Geolocation data</strong>,
                                    <strong>Device and browser details</strong>,
                                    <strong>Time and date of scans</strong>.
                                </p>
                            </div>
                            <i class="faq-toggle bi bi-chevron-right"></i>
                        </div><!-- End Faq item-->

                        <div class="faq-item">
                            <h3>How secure are dynamic QR codes?</h3>
                            <div class="faq-content">
                                <p>Dynamic QR codes are secure and encrypted. They use advanced encryption algorithms to protect your data.</p>
                            </div>
                            <i class="faq-toggle bi bi-chevron-right"></i>
                        </div><!-- End Faq item-->
                    </div>
                </div>
            </div>
        </div>
    </section><!-- /Faq Section -->
@endsection

@section('footer')
<footer id="footer" class="footer">
    <div class="container copyright text-center mt-4">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">{{ config('app.name') }}</strong> <span>All Rights Reserved</span></p>
        <div class="credits">
            <!-- All the links in the footer should remain intact. -->
            <!-- You can delete the links only if you've purchased the pro version. -->
            <!-- Licensing information: https://bootstrapmade.com/license/ -->
            <!-- Purchase the pro version with working PHP/AJAX contact form: [buy-url] -->
            Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
        </div>
    </div>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
</footer>
@endsection

@push('styles')
<style>
/* Pricing Card Styles - Consistent with Landing Page */
.pricing-card {
    height: 100%;
    padding: 2rem;
    background: var(--surface-color, #ffffff);
    border: 2px solid #dee2e6;
    border-radius: 1rem;
    transition: all 0.3s ease;
    position: relative;
    display: block;
    text-decoration: none;
    color: inherit;
}

.pricing-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
    text-decoration: none;
    color: inherit;
}

/* Popular Card Styling */
.pricing-card.popular {
    background: var(--accent-color, #007bff);
    color: var(--contrast-color, #ffffff);
    border-color: var(--accent-color, #007bff);
}

.pricing-card.popular h3,
.pricing-card.popular h4 {
    color: var(--contrast-color, #ffffff);
}

.pricing-card.popular .price .currency,
.pricing-card.popular .price .amount,
.pricing-card.popular .price .period {
    color: var(--contrast-color, #ffffff);
}

.pricing-card.popular .features-list li {
    color: var(--contrast-color, #ffffff);
}

.pricing-card.popular .features-list li i {
    color: var(--contrast-color, #ffffff);
}

.pricing-card.popular .btn-light {
    background: var(--contrast-color, #ffffff);
    color: var(--accent-color, #007bff);
    border: none;
}

.pricing-card.popular .btn-light:hover {
    background: rgba(255, 255, 255, 0.9);
    transform: translateY(-1px);
}

.pricing-card .popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--contrast-color, #ffffff);
    color: var(--accent-color, #007bff);
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.08);
    z-index: 10;
}

.pricing-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--heading-color, #333);
}

.pricing-card .price {
    margin-bottom: 1.5rem;
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.25rem;
}

.pricing-card .price .currency {
    font-size: 1.5rem;
    font-weight: 600;
    vertical-align: top;
    line-height: 1;
    color: var(--accent-color, #007bff);
}

.pricing-card .price .amount {
    font-size: 3.5rem;
    font-weight: 700;
    line-height: 1;
    color: var(--accent-color, #007bff);
}

.pricing-card .price .period {
    font-size: 1rem;
    color: #6c757d;
}

.pricing-card h4 {
    font-size: 1.125rem;
    margin-bottom: 1rem;
    color: var(--heading-color, #333);
}

.pricing-card .features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
    text-align: left;
}

.pricing-card .features-list li {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.pricing-card .features-list li i {
    color: var(--accent-color, #007bff);
    margin-right: 0.75rem;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.pricing-card .btn {
    width: 100%;
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-weight: 500;
    border-radius: 50px;
    text-decoration: none;
}

.pricing-card .btn.btn-primary {
    background: var(--accent-color, #007bff);
    border: none;
    color: var(--contrast-color, #ffffff);
}

.pricing-card .btn.btn-primary:hover {
    background: rgba(0, 123, 255, 0.85);
    transform: translateY(-1px);
    text-decoration: none;
    color: white;
}

.value-proposition {
    background: rgba(0, 123, 255, 0.05);
    padding: 2rem;
    border-radius: 1rem;
}

/* CSS Variables for consistency */
:root {
    --accent-color: #007bff;
    --heading-color: #333;
    --surface-color: #ffffff;
    --contrast-color: #ffffff;
}
</style>
@endpush
