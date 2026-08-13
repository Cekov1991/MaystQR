@extends('layouts.site')

@section('title', 'Email — ' . $qrCode->name)
@section('description', 'Send an email')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $email = data_get($qrCode->qr_content_data, 'email');
        $subject = data_get($qrCode->qr_content_data, 'subject');
        $body = data_get($qrCode->qr_content_data, 'body');
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">Email</p>
            <h1 class="eq-scan-title">{{ $email }}</h1>
            <p class="eq-scan-note">Opens in your email app with the message ready to send.</p>
        </div>

        <div class="eq-card">
            <ul class="eq-details">
                <li>
                    <span class="eq-detail-label">To</span>
                    <span class="eq-detail-value">{{ $email }}</span>
                </li>
                @if (! empty($subject))
                    <li>
                        <span class="eq-detail-label">Subject</span>
                        <span class="eq-detail-value">{{ $subject }}</span>
                    </li>
                @endif
            </ul>

            @if (! empty($body))
                <p class="eq-scan-message eq-muted" style="margin-bottom:24px">{{ $body }}</p>
            @endif

            <div class="eq-scan-actions">
                <a href="{{ $qrCode->formated_content }}" class="eq-btn eq-btn-primary">Write this email</a>
                <button type="button" class="eq-btn eq-btn-outline" data-copy="{{ $email }}">Copy address</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @include('qr.partials.copy-script')
@endpush
