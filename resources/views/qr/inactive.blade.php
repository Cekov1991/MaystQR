@extends('layouts.qr')

@section('title', 'QR Code Not Active')
@section('description', 'This QR code is not currently active.')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">

                        @if ($viewerIsOwner)
                            {{-- The owner scanned their own code: give them the fix. --}}
                            <div class="mb-4">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                            </div>

                            <h1 class="h3 mb-3">Your subscription is inactive</h1>

                            <p class="text-muted mb-4">
                                “{{ $qrCode->name }}” and your other dynamic QR codes stop working
                                when scanned until you reactivate.
                            </p>

                            <div class="bg-light rounded p-4 mb-4">
                                <div class="row text-center">
                                    <div class="col-6 border-end">
                                        <div class="h4 mb-0">{{ $offlineCodeCount }}</div>
                                        <small class="text-muted">
                                            {{ \Illuminate\Support\Str::plural('dynamic code', $offlineCodeCount) }} offline
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <div class="h4 mb-0">{{ $missedScanCount }}</div>
                                        <small class="text-muted">
                                            {{ \Illuminate\Support\Str::plural('scan', $missedScanCount) }} missed on this code
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('filament.admin.pages.billing') }}" class="btn btn-primary btn-lg px-4">
                                Reactivate my subscription
                            </a>

                            <p class="text-muted mt-4 mb-0">
                                <small>Your static QR codes are unaffected and keep working.</small>
                            </p>
                        @else
                            {{-- A stranger with a phone. No owner identity, no destination,
                                 no analytics — nothing they could not have known already. --}}
                            <div class="mb-4">
                                <i class="bi bi-qr-code text-secondary" style="font-size: 4rem;"></i>
                            </div>

                            <h1 class="h3 mb-3">This QR code isn’t active right now</h1>

                            <p class="text-muted mb-4">
                                The code you scanned is not currently in service.
                                If you were expecting to reach something specific,
                                please contact whoever shared this code with you.
                            </p>

                            <hr class="my-4">

                            <p class="text-muted mb-3"><small>Made with {{ config('app.name') }}</small></p>

                            <a href="{{ route('welcome') }}" class="btn btn-outline-primary">
                                Create your own QR code
                            </a>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
