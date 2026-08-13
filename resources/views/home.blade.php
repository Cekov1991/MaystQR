<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Create your QR code</title>
    <meta name="description" content="Create a free static QR code instantly — no account needed. Upgrade to dynamic QR codes with editable destinations and scan analytics.">
    <meta name="keywords" content="QR Code, Free QR Code Generator, Dynamic QR, Analytics, Editable QR Codes, Trackable QR Codes">

    <!-- Favicons -->
    <link href="{{ asset('landing/assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('landing/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #FBFDFE;
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            color: #0A0F24;
        }
        a { color: #2C7790; }
        a:hover { color: #235F73; }
        input::placeholder { color: #a3a8b5; }

        .eq-input {
            font-family: 'Inter', sans-serif;
            font-size: 14.5px;
            padding: 12px 14px;
            border-radius: 9px;
            border: 1.5px solid rgba(10, 15, 36, 0.15);
            color: #0A0F24;
            outline: none;
            width: 100%;
        }
        .eq-input:focus { border-color: #3A9FC0; }

        .eq-btn {
            border: none;
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            color: #ffffff;
            text-decoration: none;
            text-align: center;
        }
        .eq-btn-primary { background: #348FAD; }
        .eq-btn-primary:hover { background: #2C7790; color: #ffffff; }
        .eq-btn-primary:disabled { background: #ADD8E6; cursor: wait; }
        .eq-btn-dark { background: #0A0F24; }
        .eq-btn-dark:hover { background: #232a47; color: #ffffff; }

        .eq-card {
            width: 400px;
            max-width: 100%;
            background: #ffffff;
            border: 1px solid rgba(10, 15, 36, 0.08);
            border-radius: 16px;
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 2px rgba(10, 15, 36, 0.04);
        }
        .eq-card ul {
            list-style: none;
            margin: 0 0 28px;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .eq-card ul li {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 14px;
            color: #0A0F24;
        }

        .eq-result-panel[hidden] { display: none; }
        .eq-result-panel {
            width: 100%;
            max-width: 920px;
            margin-top: 28px;
            padding: 32px;
            background: #D4EBF2;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }
        .eq-result-panel img {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            background: #ffffff;
        }
        .eq-result-info {
            flex: 1;
            min-width: 260px;
            max-width: 480px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .eq-result-info h2 {
            font-family: 'Manrope', sans-serif;
            font-weight: 700;
            font-size: 20px;
            margin: 0;
        }
        .eq-result-info p {
            font-size: 14px;
            line-height: 1.6;
            color: #5a5f6e;
            margin: 0;
        }
        .eq-btn-outline {
            background: #ffffff;
            color: #0A0F24;
            border: 1.5px solid rgba(10, 15, 36, 0.15);
        }
        .eq-btn-outline:hover { border-color: #3A9FC0; color: #0A0F24; }

        .eq-topnav {
            font-size: 14px;
            font-weight: 600;
        }
        .eq-topnav a { color: #0A0F24; text-decoration: none; }
        .eq-topnav a:hover { color: #2C7790; }

        .eq-error {
            display: none;
            font-size: 13px;
            color: #d92d20;
            margin: 0;
        }
    </style>
</head>

<body>

    <div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:28px 24px 80px">

        <header style="width:100%;max-width:920px;display:flex;align-items:center;justify-content:space-between;margin-bottom:56px">
            <a href="{{ route('welcome') }}" style="display:flex;align-items:center">
                <img src="{{ asset('images/easy-qr-logo-trim.png') }}" alt="EasyQR" style="height:60px;width:auto;display:block">
            </a>
            <nav class="eq-topnav">
                @auth
                    <a href="{{ route('filament.admin.pages.dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}">Log in</a>
                @endauth
            </nav>
        </header>

        <div style="text-align:center;max-width:560px;margin-bottom:56px">
            <h1 style="font-family:'Manrope',sans-serif;font-weight:800;font-size:42px;line-height:1.15;margin:0 0 14px;letter-spacing:-0.01em">
                Create your QR code
            </h1>
            <p style="font-size:17px;line-height:1.5;color:#5a5f6e;margin:0">
                Choose static for a free, one-time code, or dynamic if you need to edit and track it later.
            </p>
        </div>

        <div style="display:flex;gap:28px;flex-wrap:wrap;justify-content:center;max-width:920px">

            {{-- Static QR — free, generated on the fly, never stored --}}
            <div class="eq-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
                    <span style="font-family:'Manrope',sans-serif;font-weight:700;font-size:20px">Static QR</span>
                    <span style="font-size:11px;font-weight:600;background:#D4EBF2;padding:4px 10px;border-radius:100px;letter-spacing:0.03em">FREE</span>
                </div>
                <p style="font-size:14.5px;line-height:1.6;color:#5a5f6e;margin:0 0 24px">
                    Generated instantly and shown on screen. Nothing is stored — refresh the page and it's gone, so download it right away.
                </p>
                <ul>
                    <li><span style="color:#3A9FC0;font-weight:700">✓</span>No account needed</li>
                    <li><span style="color:#3A9FC0;font-weight:700">✓</span>Free, forever</li>
                    <li><span style="color:#3A9FC0;font-weight:700">✓</span>Instant download (PNG/SVG)</li>
                </ul>
                <form id="static-qr-form" style="margin-top:auto;display:flex;flex-direction:column;gap:10px">
                    <label for="static-url" style="font-size:12.5px;font-weight:600">Website or link</label>
                    <input type="text" id="static-url" class="eq-input" placeholder="https://example.com" autocomplete="off">
                    <button type="submit" id="static-generate" class="eq-btn eq-btn-primary">Generate QR Code</button>
                    <p id="static-error" class="eq-error"></p>
                </form>
            </div>

            {{-- Dynamic QR — teaser card; creation lives in the dashboard --}}
            <div class="eq-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
                    <span style="font-family:'Manrope',sans-serif;font-weight:700;font-size:20px">Dynamic QR</span>
                    <span style="font-size:11px;font-weight:600;color:#ffffff;background:#0A0F24;padding:4px 10px;border-radius:100px;letter-spacing:0.03em">PAID</span>
                </div>
                <p style="font-size:14.5px;line-height:1.6;color:#5a5f6e;margin:0 0 24px">
                    Saved to your account. Change where it points anytime without reprinting, and see how many times it's been scanned.
                </p>
                <ul>
                    <li><span style="font-weight:700">✓</span>Editable destination, anytime</li>
                    <li><span style="font-weight:700">✓</span>Scan tracking &amp; analytics</li>
                    <li><span style="font-weight:700">✓</span>Requires login &amp; a paid plan</li>
                </ul>
                <div style="margin-top:auto;display:flex;flex-direction:column;align-items:center;gap:12px;padding:28px 20px;background:#D4EBF2;border-radius:12px;text-align:center">
                    @auth
                        <span style="font-size:13.5px;color:#5a5f6e">Create and manage dynamic QRs in your dashboard</span>
                        <a href="{{ route('filament.admin.resources.qr-codes.create') }}" class="eq-btn eq-btn-dark" style="padding:12px 24px;font-size:14px">Open Dashboard</a>
                    @else
                        <span style="font-size:13.5px;color:#5a5f6e">Log in to generate a dynamic QR</span>
                        <a href="{{ route('filament.admin.auth.login') }}" class="eq-btn eq-btn-dark" style="padding:12px 24px;font-size:14px">Log In</a>
                        <a href="{{ route('filament.admin.auth.register') }}" style="font-size:13.5px">or create an account</a>
                    @endauth
                </div>
            </div>

        </div>

        {{-- QR result — lives outside the cards so generating never changes their height --}}
        <div id="static-result" class="eq-result-panel" hidden>
            <img id="static-qr-img" src="" alt="Your QR code">
            <div class="eq-result-info">
                <h2>Your QR code is ready</h2>
                <p>Scan it with your phone to test it, then download it. Nothing is stored on our side — once you leave this page, the code is gone.</p>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a id="static-download-png" href="#" download="qr-code.png" class="eq-btn eq-btn-primary" style="padding:12px 24px;font-size:14px">Download PNG</a>
                    <a id="static-download-svg" href="#" download="qr-code.svg" class="eq-btn eq-btn-outline" style="padding:12px 24px;font-size:14px">Download SVG</a>
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

        <footer style="margin-top:auto;padding-top:72px;font-size:13px;color:#5a5f6e;display:flex;gap:18px;flex-wrap:wrap;justify-content:center">
            <span>© {{ date('Y') }} {{ config('app.name') }}</span>
            <a href="{{ url('/terms-and-conditions') }}" style="color:#5a5f6e">Terms &amp; Conditions</a>
            <a href="{{ url('/privacy-policy') }}" style="color:#5a5f6e">Privacy Policy</a>
        </footer>

    </div>

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

            function showError(message) {
                error.textContent = message;
                error.style.display = 'block';
                result.hidden = true;
            }

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
                            showError('Too many requests — please wait a minute and try again.');
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

    @include('partials.cookie-banner')
</body>

</html>
