@extends('layouts.site')

@section('title', 'Event — ' . $qrCode->name)
@section('description', 'Calendar event details')
@section('robots', 'noindex, nofollow')

@section('content')

    @php
        $data = $qrCode->qr_content_data;
        $summary = data_get($data, 'summary');
        $startDate = data_get($data, 'start_date');
        $endDate = data_get($data, 'end_date');
        $location = data_get($data, 'location');
        $description = data_get($data, 'description');

        $start = $startDate ? \Carbon\Carbon::parse($startDate) : null;
        $end = $endDate ? \Carbon\Carbon::parse($endDate) : null;

        $googleUrl = 'https://calendar.google.com/calendar/render?' . http_build_query(array_filter([
            'action' => 'TEMPLATE',
            'text' => $summary,
            'dates' => $start && $end ? $start->format('Ymd\THis\Z') . '/' . $end->format('Ymd\THis\Z') : null,
            'details' => $description,
            'location' => $location,
        ]));

        $outlookUrl = 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query(array_filter([
            'subject' => $summary,
            'startdt' => $start?->toISOString(),
            'enddt' => $end?->toISOString(),
            'body' => $description,
            'location' => $location,
        ]));
    @endphp

    <div class="eq-scan">
        <div class="eq-scan-head">
            <p class="eq-scan-eyebrow">Event</p>
            <h1 class="eq-scan-title">{{ $summary }}</h1>
            <p class="eq-scan-note">Add it to your calendar so you don’t lose the details.</p>
        </div>

        <div class="eq-card">
            <ul class="eq-details">
                @if ($start)
                    <li>
                        <span class="eq-detail-label">Starts</span>
                        <span class="eq-detail-value">{{ $start->format('D, M j Y · g:i A') }}</span>
                    </li>
                @endif
                @if ($end)
                    <li>
                        <span class="eq-detail-label">Ends</span>
                        <span class="eq-detail-value">{{ $end->format('D, M j Y · g:i A') }}</span>
                    </li>
                @endif
                @if ($location)
                    <li>
                        <span class="eq-detail-label">Where</span>
                        <span class="eq-detail-value">{{ $location }}</span>
                    </li>
                @endif
            </ul>

            @if ($description)
                <p class="eq-scan-message eq-muted" style="margin-bottom:24px">{{ $description }}</p>
            @endif

            <div class="eq-scan-actions">
                <a href="{{ $googleUrl }}" target="_blank" rel="noopener" class="eq-btn eq-btn-primary">Add to Google Calendar</a>
                <a href="{{ $outlookUrl }}" target="_blank" rel="noopener" class="eq-btn eq-btn-outline">Add to Outlook</a>
            </div>
        </div>
    </div>

@endsection
