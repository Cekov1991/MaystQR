@extends('layouts.site')

@section('title', $qrCode->name)
@section('description', 'Shared text')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $text = data_get($qrCode->qr_content_data, 'text');
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">Shared text</p>
            <h1 class="eq-scan-title">{{ $qrCode->name }}</h1>
        </div>

        <div class="eq-card">
            <p class="eq-scan-message">{{ $text }}</p>

            <div class="eq-scan-actions" style="margin-top:24px">
                <button type="button" class="eq-btn eq-btn-primary" data-copy="{{ $text }}">Copy text</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @include('qr.partials.copy-script')
@endpush
