@extends('layouts.site')

@section('title', 'Location — ' . $qrCode->name)
@section('description', 'Location details')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $latitude = data_get($qrCode->qr_content_data, 'latitude');
        $longitude = data_get($qrCode->qr_content_data, 'longitude');
        $coordinates = $latitude . ',' . $longitude;
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">Location</p>
            <h1 class="eq-scan-title">{{ $qrCode->name }}</h1>
            <p class="eq-scan-note">Open it in your maps app to get directions.</p>
        </div>

        <div class="eq-card">
            <ul class="eq-details">
                <li>
                    <span class="eq-detail-label">Latitude</span>
                    <span class="eq-detail-value eq-detail-value--mono">{{ $latitude }}</span>
                </li>
                <li>
                    <span class="eq-detail-label">Longitude</span>
                    <span class="eq-detail-value eq-detail-value--mono">{{ $longitude }}</span>
                </li>
            </ul>

            <div class="eq-scan-actions">
                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($coordinates) }}"
                    target="_blank" rel="noopener" class="eq-btn eq-btn-primary">Open in Google Maps</a>
                <a href="https://maps.apple.com/?ll={{ urlencode($coordinates) }}"
                    target="_blank" rel="noopener" class="eq-btn eq-btn-outline">Open in Apple Maps</a>
                <button type="button" class="eq-btn eq-btn-outline" data-copy="{{ $coordinates }}">Copy coordinates</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @include('qr.partials.copy-script')
@endpush
