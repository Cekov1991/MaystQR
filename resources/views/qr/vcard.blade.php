@extends('layouts.site')

@section('title', 'Contact - ' . $qrCode->name)
@section('description', 'Contact details')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $data = $qrCode->qr_content_data;
        $fullName = trim(data_get($data, 'first_name') . ' ' . data_get($data, 'last_name'));
        $organization = data_get($data, 'organization');
        $jobTitle = data_get($data, 'title');
        $phone = data_get($data, 'phone');
        $email = data_get($data, 'email');
        $website = data_get($data, 'website');
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">Contact</p>
            <h1 class="eq-scan-title">{{ $fullName }}</h1>
            @if ($jobTitle || $organization)
                <p class="eq-scan-note">
                    {{ collect([$jobTitle, $organization])->filter()->join(' · ') }}
                </p>
            @endif
        </div>

        <div class="eq-card">
            <ul class="eq-details">
                @if ($phone)
                    <li>
                        <span class="eq-detail-label">Phone</span>
                        <span class="eq-detail-value"><a href="tel:{{ $phone }}">{{ $phone }}</a></span>
                    </li>
                @endif
                @if ($email)
                    <li>
                        <span class="eq-detail-label">Email</span>
                        <span class="eq-detail-value"><a href="mailto:{{ $email }}">{{ $email }}</a></span>
                    </li>
                @endif
                @if ($website)
                    <li>
                        <span class="eq-detail-label">Website</span>
                        <span class="eq-detail-value"><a href="{{ $website }}" target="_blank" rel="noopener">{{ $website }}</a></span>
                    </li>
                @endif
            </ul>

            <div class="eq-scan-actions">
                @if ($phone)
                    <a href="tel:{{ $phone }}" class="eq-btn eq-btn-primary">Call {{ $fullName }}</a>
                @endif
                @if ($email)
                    <a href="mailto:{{ $email }}" class="eq-btn eq-btn-outline">Send an email</a>
                @endif
                @if ($website)
                    <a href="{{ $website }}" target="_blank" rel="noopener" class="eq-btn eq-btn-outline">Visit website</a>
                @endif
            </div>
        </div>
    </div>

@endsection
