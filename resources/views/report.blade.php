@extends('layouts.site')

@section('title', 'Report a QR code - ' . config('app.name'))
@section('description', 'Report a QR code on ' . config('site.domain') . ' that leads to phishing, malware, illegal content or harassment.')
@section('robots', 'noindex')

@section('content')

    <div class="eq-hero">
        <h1 class="eq-h1">Report a QR code</h1>
        <p class="eq-lead">
            If a QR code on our domain leads somewhere harmful, tell us and we will investigate.
        </p>
    </div>

    <div class="eq-card" style="max-width:640px">

        @if (session('status') === 'report-received')
            <div class="eq-panel" style="margin-bottom:24px">
                <p style="margin:0"><strong>Thank you — we have your report.</strong></p>
                <p style="margin:8px 0 0;font-size:14px" class="eq-muted">
                    We review every report. If you left an email address we will tell you what we did.
                </p>
            </div>
        @endif

        <p class="eq-card-text">
            You do not need an account, and you do not have to give us your email address.
            We review every report. Codes that break our
            <a href="{{ url('/terms-and-conditions') }}">Terms</a> are disabled, and the accounts
            behind them can be suspended.
        </p>

        <form method="POST" action="{{ route('report.store') }}" class="eq-card-form">
            @csrf

            <label for="code_url" class="eq-label">The QR code or link *</label>
            <input type="text" name="code_url" id="code_url" class="eq-input"
                value="{{ old('code_url') }}"
                placeholder="{{ 'https://' . config('site.domain') . '/q/abc123' }}" autocomplete="off">
            <p style="margin:-4px 0 0;font-size:13px" class="eq-muted">
                Paste the link, or type whatever you can read off the code. A partial reference is fine.
            </p>
            @error('code_url')
                <p class="eq-error" style="display:block">{{ $message }}</p>
            @enderror

            <label for="reason" class="eq-label">What is wrong with it? *</label>
            <select name="reason" id="reason" class="eq-input">
                <option value="">Choose one…</option>
                @foreach (\App\Http\Requests\StoreAbuseReportRequest::REASONS as $reason)
                    <option value="{{ $reason }}" @selected(old('reason') === $reason)>
                        {{ str($reason)->replace('_', ' ')->ucfirst() }}
                    </option>
                @endforeach
            </select>
            @error('reason')
                <p class="eq-error" style="display:block">{{ $message }}</p>
            @enderror

            <label for="details" class="eq-label">What did you find? *</label>
            <textarea name="details" id="details" class="eq-input" rows="5"
                placeholder="Where did the code take you, and what was there?">{{ old('details') }}</textarea>
            @error('details')
                <p class="eq-error" style="display:block">{{ $message }}</p>
            @enderror

            <label for="reporter_email" class="eq-label">Your email (optional)</label>
            <input type="email" name="reporter_email" id="reporter_email" class="eq-input"
                value="{{ old('reporter_email') }}" placeholder="Only if you want a reply" autocomplete="off">
            @error('reporter_email')
                <p class="eq-error" style="display:block">{{ $message }}</p>
            @enderror

            <button type="submit" class="eq-btn eq-btn-primary">Send report</button>
        </form>

        <p style="margin:20px 0 0;font-size:13px" class="eq-muted">
            You can also email us directly at
            <a href="mailto:{{ config('site.support_email') }}">{{ config('site.support_email') }}</a>.
        </p>

    </div>

@endsection
