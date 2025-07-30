@extends('layouts.qr')

@section('title', 'Call Phone - {{ $qrCode->name }}')
@section('description', 'Make a phone call')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- Phone Icon -->
                        <div class="mb-4">
                            <i class="bi bi-telephone text-success" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-success mb-3">Make a Call</h1>
                        <h3 class="h4 mb-4">{{ $qrCode->qr_content_data['phone'] }}</h3>

                        <!-- Action Button -->
                        <div class="mt-4">
                            <a href="{{ $qrCode->formated_content }}" class="btn btn-success btn-lg">
                                <i class="bi bi-telephone me-2"></i>Call Now
                            </a>
                        </div>

                        <!-- Copy Phone Button -->
                        <div class="mt-3">
                            <button class="btn btn-outline-secondary" onclick="copyPhone()">
                                <i class="bi bi-clipboard me-2"></i>Copy Phone Number
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function copyPhone() {
    const phone = '{{ $qrCode->qr_content_data['phone'] }}';
    navigator.clipboard.writeText(phone).then(() => {
        const btn = event.target;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check me-2"></i>Copied!';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}
</script>
@endsection