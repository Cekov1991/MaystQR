@extends('layouts.site')

@section('title', config('app.name') . ' - Create your QR code')
@section('description', 'Create a free static QR code instantly, with no account needed. Upgrade to dynamic QR codes with editable destinations and scan analytics.')
@section('keywords', 'QR Code, Free QR Code Generator, Dynamic QR, Analytics, Editable QR Codes, Trackable QR Codes')

@push('structured-data')
    <script type="application/ld+json">{!! \App\Support\StructuredData::forProduct() !!}</script>
@endpush

@section('content')

    <div class="eq-hero">
        <h1 class="eq-h1">Create your QR code</h1>
        <p class="eq-lead">
            Choose static for a free, one-time code, or dynamic if you need to edit and track it later.
        </p>
    </div>

    <div class="eq-card-grid">

        {{-- Static QR: free, generated on the fly, never stored --}}
        <div class="eq-card eq-card--choice">
            <div class="eq-card-head">
                <span class="eq-card-title">Static QR</span>
                <span class="eq-badge">FREE</span>
            </div>
            <p class="eq-price">Free<span class="eq-price-period">forever</span></p>
            <p class="eq-price-note">No account, no payment, no expiry.</p>
            <p class="eq-card-text">
                Generated instantly and shown on screen. We never store your code or its link. Refresh the page and it’s gone, so download it right away.
            </p>
            <ul class="eq-check-list">
                <li><span class="eq-check">✓</span>No account needed</li>
                <li><span class="eq-check">✓</span>Keeps working permanently</li>
                <li><span class="eq-check">✓</span>Instant download (PNG/SVG)</li>
            </ul>
            <form id="static-qr-form" class="eq-card-form">
                <label for="static-url" class="eq-label">Website or link</label>
                <input type="text" id="static-url" class="eq-input" placeholder="https://example.com" autocomplete="off">
                <button type="submit" id="static-generate" class="eq-btn eq-btn-primary">Generate QR Code</button>
                <p id="static-error" class="eq-error"></p>
            </form>
        </div>

        {{-- Dynamic QR: teaser card, creation lives in the dashboard --}}
        <div class="eq-card eq-card--choice">
            <div class="eq-card-head">
                <span class="eq-card-title">Dynamic QR</span>
                <span class="eq-badge eq-badge--dark">PAID</span>
            </div>
            <p class="eq-price">
                {{ \App\Support\SubscriptionPrice::formatted() }}<span class="eq-price-period">per year</span>
            </p>
            <p class="eq-price-note">
                {{ config('subscription.trial_days') }}-day free trial, no payment details needed.
                Tax included. <a href="{{ route('pricing') }}">See what’s included</a>
            </p>
            <p class="eq-card-text">
                Saved to your account. Change where it points anytime without reprinting, and see how many times it’s been scanned.
            </p>
            <ul class="eq-check-list">
                <li><span class="eq-check eq-check--ink">✓</span>Editable destination, anytime</li>
                <li><span class="eq-check eq-check--ink">✓</span>Scan tracking &amp; analytics</li>
                <li><span class="eq-check eq-check--ink">✓</span>Cancel anytime, keep access to period end</li>
            </ul>
            <div class="eq-panel eq-card-foot">
                @auth
                    <span style="font-size:13.5px" class="eq-muted">Create and manage dynamic QRs in your dashboard</span>
                    <a href="{{ route('filament.admin.resources.qr-codes.create') }}" class="eq-btn eq-btn-dark eq-btn--sm">Open Dashboard</a>
                @else
                    <span style="font-size:13.5px" class="eq-muted">Log in to generate a dynamic QR</span>
                    <a href="{{ route('filament.admin.auth.login') }}" class="eq-btn eq-btn-dark eq-btn--sm">Log In</a>
                    <a href="{{ route('filament.admin.auth.register') }}" style="font-size:13.5px">or create an account</a>
                @endauth
            </div>
        </div>

    </div>

    {{-- QR result: lives outside the cards so generating never changes their height --}}
    <div id="static-result" class="eq-result-panel" hidden>
        <img id="static-qr-img" src="" alt="Your QR code">
        <div class="eq-result-info">
            <h2 class="eq-h2">Your QR code is ready</h2>
            <p>Scan it with your phone to test it, then download it. We never store your code or its link, so once you leave this page it’s gone.</p>
            <div class="eq-actions">
                <a id="static-download-png" href="#" download="qr-code.png" class="eq-btn eq-btn-primary eq-btn--sm">Download PNG</a>
                <a id="static-download-svg" href="#" download="qr-code.svg" class="eq-btn eq-btn-outline eq-btn--sm">Download SVG</a>
            </div>
            <p style="font-size:13px">
                A static code can never be changed. Need to edit the link later or track scans?
                @auth
                    <a href="{{ route('filament.admin.resources.qr-codes.create') }}">Create a dynamic QR</a>
                @else
                    <a href="{{ route('filament.admin.auth.register') }}">Create a dynamic QR</a>
                @endauth
            </p>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('static-qr-form');
            const input = document.getElementById('static-url');
            const button = document.getElementById('static-generate');
            const error = document.getElementById('static-error');
            const result = document.getElementById('static-result');
            const image = document.getElementById('static-qr-img');
            const downloadPng = document.getElementById('static-download-png');
            const downloadSvg = document.getElementById('static-download-svg');
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            /**
             * Reports one of the four events that only this page can see. Event
             * names are rendered from the TrackedEvent enum rather than typed
             * here, so a renamed case cannot leave the page quietly posting a
             * value the endpoint no longer accepts.
             *
             * Fire and forget in both directions: the response is ignored, and a
             * failure is swallowed. Counting a download must never be the reason
             * a download does not happen. `keepalive` is what lets the request
             * survive if the click does navigate away.
             */
            function logEvent(event, extra) {
                fetch('{{ route('events.log') }}', {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(Object.assign({ event: event }, extra || {})),
                }).catch(function () {});
            }

            function showError(message) {
                error.textContent = message;
                error.style.display = 'block';
                result.hidden = true;
            }

            /*
             * A download is a click on a data: URI, which never reaches the
             * server — so the click is the only evidence it happened. The
             * listeners are attached once, not per generation, and the format
             * tells the two buttons apart.
             */
            downloadPng.addEventListener('click', function () {
                logEvent('{{ \App\Enums\TrackedEvent::QrDownloaded->value }}', { format: 'png' });
            });

            downloadSvg.addEventListener('click', function () {
                logEvent('{{ \App\Enums\TrackedEvent::QrDownloaded->value }}', { format: 'svg' });
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const url = input.value.trim();
                if (!url) {
                    showError('Please enter a URL.');
                    return;
                }

                button.disabled = true;
                button.textContent = 'Generating…';
                error.style.display = 'none';

                try {
                    const response = await fetch('{{ route('qr.instant') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ url: url }),
                    });

                    const data = await response.json().catch(() => null);

                    if (!response.ok) {
                        if (response.status === 429) {
                            showError('Too many requests. Please wait a minute and try again.');
                        } else {
                            showError((data && (data.errors?.url?.[0] || data.message)) || 'Something went wrong. Please try again.');
                        }
                        return;
                    }

                    image.src = data.png;
                    downloadPng.href = data.png;
                    downloadSvg.href = data.svg;
                    result.hidden = false;
                    result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } catch (e) {
                    showError('Something went wrong. Please try again.');
                } finally {
                    button.disabled = false;
                    button.textContent = 'Generate QR Code';
                }
            });
        })();
    </script>
@endpush
