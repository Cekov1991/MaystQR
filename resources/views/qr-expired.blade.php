@extends('layouts.qr')

@section('title', 'QR Code Expired - ' . config('app.name'))
@section('description', 'This QR code has expired. Extend it to continue using.')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 20px; min-height: 100vh;">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- Expired Icon -->
                        <div class="mb-4">
                            <i class="bi bi-clock-history text-warning" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h4 text-danger mb-3">QR Code {{ $qrCode->name }} Expired</h1>
                        <p class="text-muted mb-4">
                            This QR code expired on
                            <strong>{{ $qrCode->expires_at->format('M j, Y \a\t g:i A') }}</strong>
                        </p>

                        {{-- @if($qrCode->isInTrial())
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                This QR code was in its 24-hour trial period.
                            </div>
                        @endif --}}

                        <!-- QR Code Info -->
                        {{-- <div class="row mb-4">
                            <div class="col-md-6 mx-auto">
                                <div class="bg-light p-3 rounded">
                                    <h5 class="mb-2">{{ $qrCode->name }}</h5>
                                    <small class="text-muted">
                                        Created: {{ $qrCode->created_at->format('M j, Y') }}
                                    </small>
                                </div>
                            </div>
                        </div> --}}

                        @auth
                            @if($qrCode->user_id === auth()->id())
                                <!-- Owner Extension Options -->
                                <div class="extension-section">
                                    <h3 class="h4 mb-4 text-primary">Extend Your QR Code</h3>
                                    <p class="mb-4">Choose an extension package to reactivate your QR code:</p>

                                    <form method="POST" action="{{ route('qr.extend', $qrCode->short_url) }}">
                                        @csrf

                                        <div class="row justify-content-center">
                                            @foreach($packages as $package)
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <div class="package-option">
                                                        <input type="radio"
                                                               name="package_id"
                                                               value="{{ $package->id }}"
                                                               id="package_{{ $package->id }}"
                                                               class="form-check-input d-none"
                                                               {{ $package->id == 2 ? 'checked' : '' }}>

                                                        <label for="package_{{ $package->id }}"
                                                               class="pricing-card h-100 {{ $package->id == 2 ? 'selected' : '' }}"
                                                               style="cursor: pointer;">
                                                            @if($package->id == 2)
                                                                <div class="popular-badge">Most Popular</div>
                                                            @endif
                                                            <div class="text-center">
                                                                <h3>{{ $package->duration_text }}</h3>
                                                                <div class="price">
                                                                    <span class="currency">${{ $package->price }}</span>
                                                                    <span class="amount"></span>
                                                                    <span class="period">one-time</span>
                                                                </div>

                                                                <h4>Extension Includes:</h4>
                                                                <ul class="features-list">
                                                                    <li>
                                                                        <i class="bi bi-check-circle-fill"></i>
                                                                        {{ $package->duration_months }} {{ $package->duration_months === 1 ? 'month' : 'months' }} extension
                                                                    </li>
                                                                    <li>
                                                                        <i class="bi bi-check-circle-fill"></i>
                                                                        Continue existing analytics
                                                                    </li>
                                                                    <li>
                                                                        <i class="bi bi-check-circle-fill"></i>
                                                                        No downtime during extension
                                                                    </li>
                                                                    <li>
                                                                        <i class="bi bi-check-circle-fill"></i>
                                                                        Instant activation
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                                <i class="bi bi-credit-card me-2"></i>
                                                Purchase Extension
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @else
                                <!-- Not Owner Message -->
                                <div class="not-owner-section">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        You are not the owner of this QR code.
                                    </div>
                                    <p class="text-muted">
                                        If you own this QR code, please
                                        <a href="{{ route('login') }}" class="text-primary">log in</a>
                                        to extend it.
                                    </p>
                                </div>
                            @endif
                        @else
                            <!-- Guest User -->
                            <div class="guest-section">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    If you're the owner of this QR code, please log in to extend it.
                                </div>

                                <div class="d-flex gap-3 justify-content-center">
                                    <a href="{{ route('login') }}" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>
                                        Login
                                    </a>
                                    <a href="{{ route('register') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-person-plus me-2"></i>
                                        Create Account
                                    </a>
                                </div>
                            </div>
                        @endauth

                        <!-- Support Info -->
                        <div class="mt-5 pt-4 border-top">
                            <small class="text-muted">
                                Need help?
                                <a href="#" class="text-primary">Contact Support</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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

.pricing-card.selected {
    border-color: var(--accent-color, #007bff);
    background-color: #f8f9ff;
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

.pricing-card.popular.selected {
    background: var(--accent-color, #007bff);
    border-color: #ffffff;
    box-shadow: 0 0 0 3px var(--accent-color, #007bff);
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

/* Override the old package card styles */
.package-option input[type="radio"]:checked + label {
    border-color: var(--accent-color, #007bff) !important;
    background-color: #f8f9ff;
}

.package-option input[type="radio"]:checked + label.popular {
    background: var(--accent-color, #007bff) !important;
    border-color: #ffffff !important;
    box-shadow: 0 0 0 3px var(--accent-color, #007bff);
}

.extension-section {
    border-top: 1px solid #dee2e6;
    padding-top: 2rem;
    margin-top: 2rem;
}

.hero {
    min-height: 100vh;
}

.card {
    border-radius: 15px;
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle package selection
    const packageInputs = document.querySelectorAll('input[name="package_id"]');
    const packageCards = document.querySelectorAll('.pricing-card');

    packageInputs.forEach((input, index) => {
        input.addEventListener('change', function() {
            // Reset all cards
            packageCards.forEach(card => {
                card.classList.remove('selected');
            });

            // Highlight selected card
            if (this.checked) {
                packageCards[index].classList.add('selected');
            }
        });
    });

    // Make cards clickable
    packageCards.forEach((card, index) => {
        card.addEventListener('click', function() {
            packageInputs[index].checked = true;
            packageInputs[index].dispatchEvent(new Event('change'));
        });
    });
});
</script>
@endpush