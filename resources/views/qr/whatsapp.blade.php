@extends('layouts.qr')

@section('title', 'WhatsApp Message - {{ $qrCode->name }}')
@section('description', 'Send a WhatsApp message')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- WhatsApp Icon -->
                        <div class="mb-4">
                            <i class="bi bi-whatsapp text-success" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-success mb-3">WhatsApp Message</h1>
                        <p class="text-muted mb-4">Click the button below to open WhatsApp</p>

                        <!-- WhatsApp Details -->
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="bg-light p-4 rounded">
                                    <div class="row text-start mb-2">
                                        <div class="col-sm-3"><strong>Phone:</strong></div>
                                        <div class="col-sm-9">+{{ $qrCode->qr_content_data['phone'] }}</div>
                                    </div>
                                    @if(!empty($qrCode->qr_content_data['message']))
                                        <div class="row text-start">
                                            <div class="col-sm-3"><strong>Message:</strong></div>
                                            <div class="col-sm-9">
                                                <div class="border p-3 rounded bg-white">
                                                    <i class="bi bi-chat-quote text-muted me-2"></i>
                                                    {{ $qrCode->qr_content_data['message'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-4">
                            <a href="{{ $qrCode->formated_content }}" class="btn btn-success btn-lg">
                                <i class="bi bi-whatsapp me-2"></i>Open WhatsApp
                            </a>
                        </div>

                        <!-- Alternative Actions -->
                        <div class="mt-3">
                            <button class="btn btn-outline-secondary me-2" onclick="copyPhone()">
                                <i class="bi bi-telephone me-2"></i>Copy Phone
                            </button>
                            @if(!empty($qrCode->qr_content_data['message']))
                                <button class="btn btn-outline-secondary" onclick="copyMessage()">
                                    <i class="bi bi-chat me-2"></i>Copy Message
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function copyPhone() {
    const phone = '+{{ $qrCode->qr_content_data['phone'] }}';
    navigator.clipboard.writeText(phone).then(() => {
        showCopySuccess(event.target, 'Phone Copied!');
    });
}

function copyMessage() {
    const message = '{{ $qrCode->qr_content_data['message'] }}';
    navigator.clipboard.writeText(message).then(() => {
        showCopySuccess(event.target, 'Message Copied!');
    });
}

function showCopySuccess(button, text) {
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bi bi-check me-2"></i>' + text;
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');

    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}
</script>
@endsection