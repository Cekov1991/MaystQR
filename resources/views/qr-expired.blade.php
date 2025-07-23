@extends('layouts.qr')

@section('title', 'QR Code Expired - MaystQR')
@section('description', 'This QR code has expired. Extend it to continue using.')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- Expired Icon -->
                        <div class="mb-4">
                            <i class="bi bi-clock-history text-warning" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-danger mb-3">QR Code Expired</h1>
                        <p class="text-muted mb-4">
                            This QR code expired on
                            <strong>{{ $qrCode->expires_at->format('M j, Y \a\t g:i A') }}</strong>
                        </p>

                        @if($qrCode->isInTrial())
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                This QR code was in its 24-hour trial period.
                            </div>
                        @endif

                        <!-- QR Code Info -->
                        <div class="row mb-4">
                            <div class="col-md-6 mx-auto">
                                <div class="bg-light p-3 rounded">
                                    <h5 class="mb-2">{{ $qrCode->name }}</h5>
                                    <small class="text-muted">
                                        Created: {{ $qrCode->created_at->format('M j, Y') }}
                                    </small>
                                </div>
                            </div>
                        </div>

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
                                                               {{ $loop->first ? 'checked' : '' }}>

                                                        <label for="package_{{ $package->id }}"
                                                               class="card h-100 package-card {{ $loop->first ? 'border-primary' : 'border-light' }}"
                                                               style="cursor: pointer; transition: all 0.3s;">
                                                            <div class="card-body text-center">
                                                                <h5 class="card-title text-primary">
                                                                    {{ $package->duration_text }}
                                                                </h5>
                                                                <div class="price mb-2">
                                                                    <span class="h3 text-success">{{ $package->formatted_price }}</span>
                                                                </div>
                                                                <small class="text-muted">
                                                                    Valid for {{ $package->duration_months }}
                                                                    {{ $package->duration_months === 1 ? 'month' : 'months' }}
                                                                </small>
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
.package-card:hover {
    border-color: #0d6efd !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.package-option input[type="radio"]:checked + label {
    border-color: #0d6efd !important;
    background-color: #f8f9ff;
}

.extension-section {
    border-top: 1px solid #dee2e6;
    padding-top: 2rem;
    margin-top: 2rem;
}

.hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.card {
    border-radius: 15px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle package selection
    const packageInputs = document.querySelectorAll('input[name="package_id"]');
    const packageCards = document.querySelectorAll('.package-card');

    packageInputs.forEach((input, index) => {
        input.addEventListener('change', function() {
            // Reset all cards
            packageCards.forEach(card => {
                card.classList.remove('border-primary');
                card.classList.add('border-light');
            });

            // Highlight selected card
            if (this.checked) {
                packageCards[index].classList.remove('border-light');
                packageCards[index].classList.add('border-primary');
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