@extends('layouts.qr')

@section('title', 'WiFi Connection - {{ $qrCode->name }}')
@section('description', 'Connect to WiFi network')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- WiFi Icon -->
                        <div class="mb-4">
                            <i class="bi bi-wifi text-primary" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-primary mb-3">Connect to WiFi</h1>
                        <h3 class="h4 mb-4">{{ $qrCode->qr_content_data['ssid'] }}</h3>

                        <!-- WiFi Details -->
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="bg-light p-4 rounded">
                                    <div class="row text-start">
                                        <div class="col-sm-4"><strong>Network:</strong></div>
                                        <div class="col-sm-8">{{ $qrCode->qr_content_data['ssid'] }}</div>
                                    </div>
                                    <hr>
                                    <div class="row text-start">
                                        <div class="col-sm-4"><strong>Security:</strong></div>
                                        <div class="col-sm-8">
                                            @if($qrCode->qr_content_data['security'] === 'nopass')
                                                Open Network (No Password)
                                            @else
                                                {{ $qrCode->qr_content_data['security'] }}
                                            @endif
                                        </div>
                                    </div>
                                    @if($qrCode->qr_content_data['security'] !== 'nopass' && !empty($qrCode->qr_content_data['password']))
                                        <hr>
                                        <div class="row text-start">
                                            <div class="col-sm-4"><strong>Password:</strong></div>
                                            <div class="col-sm-8">
                                                <code id="wifi-password">{{ $qrCode->qr_content_data['password'] }}</code>
                                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyPassword()">
                                                    <i class="bi bi-clipboard"></i> Copy
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Instructions:</strong><br>
                            1. Go to your device's WiFi settings<br>
                            2. Look for the network "{{ $qrCode->qr_content_data['ssid'] }}"<br>
                            3. Enter the password if prompted<br>
                            4. Connect to the network
                        </div>

                        <!-- Connect Button for supported devices -->
                        <div class="mt-4">
                            <a href="{{ $qrCode->formated_content }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-wifi me-2"></i>Connect Automatically
                            </a>
                        </div>

                        <p class="text-muted mt-3 small">
                            If automatic connection doesn't work, please use the manual instructions above.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function copyPassword() {
    const password = document.getElementById('wifi-password').textContent;
    navigator.clipboard.writeText(password).then(() => {
        // Show success feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
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