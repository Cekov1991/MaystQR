@extends('layouts.qr')

@section('title', 'Purchase QR Code Extension - MaystQR')
@section('description', 'Extend your QR code validity with our affordable packages.')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                            <h1 class="h2 mt-3 mb-1">Purchase Extension</h1>
                            <p class="text-muted">Extend your QR code validity</p>
                        </div>

                        <!-- QR Code Info -->
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="card border-light bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title mb-2">{{ $qrCode->name }}</h5>
                                        <p class="text-muted mb-1">
                                            <i class="bi bi-link-45deg me-1"></i>
                                            {{ url('/q/' . $qrCode->short_url) }}
                                        </p>
                                        <small class="text-muted">
                                            @if($qrCode->isExpired())
                                                Expired: {{ $qrCode->expires_at->format('M j, Y') }}
                                            @else
                                                Expires: {{ $qrCode->expires_at->format('M j, Y') }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Package Details -->
                        <div class="text-center mb-4">
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <h3 class="text-primary mb-2">{{ $package->name }}</h3>
                                            <div class="price mb-3">
                                                <span class="h2 text-success">{{ $package->formatted_price }}</span>
                                            </div>
                                            <p class="mb-2">
                                                <i class="bi bi-calendar-check text-primary me-2"></i>
                                                {{ $package->duration_text }} of validity
                                            </p>
                                            <small class="text-muted">
                                                Your QR code will be extended by {{ $package->duration_months }}
                                                {{ $package->duration_months === 1 ? 'month' : 'months' }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- New Expiration Date -->
                        <div class="alert alert-info text-center mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>New expiration date:</strong>
                            @php
                                $newExpirationDate = $qrCode->isExpired()
                                    ? now()->addMonths($package->duration_months)
                                    : $qrCode->expires_at->addMonths($package->duration_months);
                            @endphp
                            {{ $newExpirationDate->format('M j, Y \a\t g:i A') }}
                        </div>

                        <!-- Purchase Form -->
                        <form method="POST" action="{{ route('qr.package.buy', [$qrCode, $package]) }}">
                            @csrf

                            <!-- Payment Method Info -->
                            <div class="mb-4">
                                <h5 class="mb-3">Payment Method</h5>
                                <div class="d-flex align-items-center justify-content-center p-3 bg-light rounded">
                                    <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png"
                                         alt="PayPal" height="25" class="me-2">
                                    <span class="text-muted">Secure payment via PayPal</span>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label text-muted" for="terms">
                                        I agree to the
                                        <a href="#" class="text-primary">Terms of Service</a> and
                                        <a href="#" class="text-primary">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>

                            <!-- Purchase Button -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5 py-3">
                                    <i class="bi bi-paypal me-2"></i>
                                    Pay {{ $package->formatted_price }} with PayPal
                                </button>
                            </div>
                        </form>

                        <!-- Security Info -->
                        <div class="mt-4 pt-4 border-top text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-lock text-success me-1"></i>
                                Your payment is secured by PayPal's encryption
                            </small>
                        </div>

                        <!-- Back Link -->
                        <div class="text-center mt-3">
                            <a href="{{ route('qr.expired', $qrCode->short_url) }}" class="btn btn-link">
                                <i class="bi bi-arrow-left me-1"></i>
                                Back to QR Code
                            </a>
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
.hero {
    /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
    min-height: 100vh;
}

.card {
    border-radius: 15px;
}

.price {
    position: relative;
}

.btn-primary {
    /* background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); */
    border: none;
    border-radius: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
}
</style>
@endpush