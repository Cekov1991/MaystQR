@extends('layouts.site')

@section('title', 'Wi-Fi - ' . $qrCode->name)
@section('description', 'Wi-Fi network details')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $ssid = data_get($qrCode->qr_content_data, 'ssid');
        $security = data_get($qrCode->qr_content_data, 'security');
        $password = data_get($qrCode->qr_content_data, 'password');
        $isOpen = $security === 'nopass';
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">Wi-Fi network</p>
            <h1 class="eq-scan-title">{{ $ssid }}</h1>
            <p class="eq-scan-note">
                Open your Wi-Fi settings, pick this network
                @if ($isOpen)
                    and connect. No password needed.
                @else
                    and enter the password below.
                @endif
            </p>
        </div>

        <div class="eq-card">
            <ul class="eq-details">
                <li>
                    <span class="eq-detail-label">Network</span>
                    <span class="eq-detail-value">{{ $ssid }}</span>
                </li>
                <li>
                    <span class="eq-detail-label">Security</span>
                    <span class="eq-detail-value">{{ $isOpen ? 'Open, no password' : $security }}</span>
                </li>
                @if (! $isOpen && ! empty($password))
                    <li>
                        <span class="eq-detail-label">Password</span>
                        <span class="eq-detail-value eq-detail-value--mono">{{ $password }}</span>
                    </li>
                @endif
            </ul>

            @if (! $isOpen && ! empty($password))
                <div class="eq-scan-actions">
                    <button type="button" class="eq-btn eq-btn-primary" data-copy="{{ $password }}">Copy password</button>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    @include('qr.partials.copy-script')
@endpush
