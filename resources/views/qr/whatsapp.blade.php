@extends('layouts.site')

@section('title', 'WhatsApp - ' . $qrCode->name)
@section('description', 'Send a WhatsApp message')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $phone = data_get($qrCode->qr_content_data, 'phone');
        $message = data_get($qrCode->qr_content_data, 'message');
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">WhatsApp</p>
            <h1 class="eq-scan-title">+{{ $phone }}</h1>
            <p class="eq-scan-note">Opens WhatsApp with the message ready to send.</p>
        </div>

        <div class="eq-card">
            @if (! empty($message))
                <p class="eq-scan-message eq-muted" style="margin-bottom:24px">{{ $message }}</p>
            @endif

            <div class="eq-scan-actions">
                <a href="{{ $qrCode->formated_content }}" class="eq-btn eq-btn-primary">Open WhatsApp</a>
                <button type="button" class="eq-btn eq-btn-outline" data-copy="+{{ $phone }}">Copy number</button>
                @if (! empty($message))
                    <button type="button" class="eq-btn eq-btn-outline" data-copy="{{ $message }}">Copy message</button>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @include('qr.partials.copy-script')
@endpush
