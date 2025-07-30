@extends('layouts.qr')

@section('title', 'Text Content - {{ $qrCode->name }}')
@section('description', 'View text content')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- Text Icon -->
                        <div class="mb-4">
                            <i class="bi bi-file-text text-primary" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-primary mb-4">Text Content</h1>

                        <!-- Text Content -->
                        <div class="row mb-4">
                            <div class="col-md-10 mx-auto">
                                <div class="bg-light p-4 rounded text-start">
                                    <div class="border p-4 rounded bg-white">
                                        {{ $qrCode->qr_content_data['text'] }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Copy Button -->
                        <div class="mt-4">
                            <button class="btn btn-primary btn-lg" onclick="copyText()">
                                <i class="bi bi-clipboard me-2"></i>Copy Text
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function copyText() {
    const text = `{{ $qrCode->qr_content_data['text'] }}`;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check me-2"></i>Copied!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    });
}
</script>
@endsection