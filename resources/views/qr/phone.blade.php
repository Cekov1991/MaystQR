@extends('layouts.site')

@section('title', 'Call — ' . $qrCode->name)
@section('description', 'Phone number')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $phone = data_get($qrCode->qr_content_data, 'phone');
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">Phone number</p>
            <h1 class="eq-scan-title">{{ $phone }}</h1>
            <p class="eq-scan-note">Tap to call, or copy the number to use later.</p>
        </div>

        <div class="eq-card">
            <div class="eq-scan-actions">
                <a href="tel:{{ $phone }}" class="eq-btn eq-btn-primary">Call {{ $phone }}</a>
                <button type="button" class="eq-btn eq-btn-outline" data-copy="{{ $phone }}">Copy number</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @include('qr.partials.copy-script')
@endpush
