@extends('layouts.site')

@section('title', 'This QR code is not active')
@section('description', 'This QR code is not currently active.')
@section('robots', 'noindex, nofollow')

@section('content')

    <div class="eq-scan">

        @if ($viewerIsOwner)
            {{-- The owner scanned their own code: give them the fix. --}}
            <div class="eq-scan-head">
                <p class="eq-scan-eyebrow">Subscription inactive</p>
                <h1 class="eq-scan-title">Your codes have stopped resolving</h1>
                <p class="eq-scan-note">
                    “{{ $qrCode->name }}” and your other dynamic QR codes show this page instead of
                    their destination until you reactivate.
                </p>
            </div>

            <div class="eq-card">
                <div class="eq-stats">
                    <div class="eq-stat">
                        <div class="eq-stat-figure">{{ $offlineCodeCount }}</div>
                        <div class="eq-stat-label">
                            {{ \Illuminate\Support\Str::plural('dynamic code', $offlineCodeCount) }} offline
                        </div>
                    </div>
                    <div class="eq-stat">
                        <div class="eq-stat-figure">{{ $missedScanCount }}</div>
                        <div class="eq-stat-label">
                            {{ \Illuminate\Support\Str::plural('scan', $missedScanCount) }} missed on this code
                        </div>
                    </div>
                </div>

                <div class="eq-scan-actions">
                    <a href="{{ route('filament.admin.pages.billing') }}" class="eq-btn eq-btn-primary">
                        Reactivate my subscription
                    </a>
                </div>

                <p class="eq-scan-note" style="margin-top:20px;text-align:center">
                    Your static QR codes are unaffected and keep working.
                </p>
            </div>
        @else
            {{-- A stranger with a phone. No owner identity, no destination,
                 no analytics — nothing they could not have known already. --}}
            <div class="eq-scan-head">
                <h1 class="eq-scan-title">This code isn’t active right now</h1>
                <p class="eq-scan-note">
                    It isn’t currently in service. If you were expecting to reach something specific,
                    contact whoever shared the code with you.
                </p>
            </div>

            <div class="eq-card">
                <p class="eq-scan-note" style="text-align:center;margin-bottom:20px">
                    Need QR codes of your own?
                </p>
                <div class="eq-scan-actions">
                    <a href="{{ route('welcome') }}" class="eq-btn eq-btn-primary">
                        Create one free with {{ config('app.name') }}
                    </a>
                </div>
            </div>
        @endif

    </div>

@endsection
