@extends('layouts.qr')

@section('title', 'Send Email - {{ $qrCode->name }}')
@section('description', 'Send an email')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- Email Icon -->
                        <div class="mb-4">
                            <i class="bi bi-envelope text-primary" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-primary mb-3">Send Email</h1>
                        <p class="text-muted mb-4">Click the button below to open your email client</p>

                        <!-- Email Details -->
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="bg-light p-4 rounded">
                                    <div class="row text-start mb-2">
                                        <div class="col-sm-3"><strong>To:</strong></div>
                                        <div class="col-sm-9">{{ $qrCode->qr_content_data['email'] }}</div>
                                    </div>
                                    @if(!empty($qrCode->qr_content_data['subject']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>Subject:</strong></div>
                                            <div class="col-sm-9">{{ $qrCode->qr_content_data['subject'] }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['body']))
                                        <div class="row text-start">
                                            <div class="col-sm-3"><strong>Message:</strong></div>
                                            <div class="col-sm-9">
                                                <div class="border p-2 rounded bg-white">
                                                    {{ $qrCode->qr_content_data['body'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-4">
                            <a href="{{ $qrCode->formated_content }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-envelope me-2"></i>Open Email Client
                            </a>
                        </div>

                        <!-- Copy Email Button -->
                        <div class="mt-3">
                            <button class="btn btn-outline-secondary" onclick="copyEmail()">
                                <i class="bi bi-clipboard me-2"></i>Copy Email Address
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function copyEmail() {
    const email = '{{ $qrCode->qr_content_data['email'] }}';
    navigator.clipboard.writeText(email).then(() => {
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