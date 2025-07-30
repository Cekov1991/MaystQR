@extends('layouts.qr')

@section('title', 'Calendar Event - {{ $qrCode->name }}')
@section('description', 'Add calendar event')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- Calendar Icon -->
                        <div class="mb-4">
                            <i class="bi bi-calendar-event text-primary" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-primary mb-3">Calendar Event</h1>
                        <h3 class="h4 mb-4">{{ $qrCode->qr_content_data['summary'] }}</h3>

                        <!-- Event Details -->
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="bg-light p-4 rounded">
                                    <div class="row text-start mb-2">
                                        <div class="col-sm-3"><strong>Event:</strong></div>
                                        <div class="col-sm-9">{{ $qrCode->qr_content_data['summary'] }}</div>
                                    </div>
                                    @if(!empty($qrCode->qr_content_data['start_date']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>Start:</strong></div>
                                            <div class="col-sm-9">{{ \Carbon\Carbon::parse($qrCode->qr_content_data['start_date'])->format('M j, Y g:i A') }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['end_date']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>End:</strong></div>
                                            <div class="col-sm-9">{{ \Carbon\Carbon::parse($qrCode->qr_content_data['end_date'])->format('M j, Y g:i A') }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['location']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>Location:</strong></div>
                                            <div class="col-sm-9">{{ $qrCode->qr_content_data['location'] }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['description']))
                                        <div class="row text-start">
                                            <div class="col-sm-3"><strong>Description:</strong></div>
                                            <div class="col-sm-9">
                                                <div class="border p-2 rounded bg-white">
                                                    {{ $qrCode->qr_content_data['description'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-4">
                            <a href="{{ $qrCode->formated_content }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-calendar-plus me-2"></i>Add to Calendar
                            </a>
                        </div>

                        <!-- Calendar App Buttons -->
                        <div class="mt-3">
                            <div class="btn-group" role="group">
                                <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text={{ urlencode($qrCode->qr_content_data['summary']) }}&dates={{ \Carbon\Carbon::parse($qrCode->qr_content_data['start_date'])->format('Ymd\THis\Z') }}/{{ \Carbon\Carbon::parse($qrCode->qr_content_data['end_date'])->format('Ymd\THis\Z') }}&details={{ urlencode($qrCode->qr_content_data['description'] ?? '') }}&location={{ urlencode($qrCode->qr_content_data['location'] ?? '') }}"
                                   target="_blank" class="btn btn-outline-primary">
                                    <i class="bi bi-google me-2"></i>Google Calendar
                                </a>
                                <a href="https://outlook.live.com/calendar/0/deeplink/compose?subject={{ urlencode($qrCode->qr_content_data['summary']) }}&startdt={{ \Carbon\Carbon::parse($qrCode->qr_content_data['start_date'])->toISOString() }}&enddt={{ \Carbon\Carbon::parse($qrCode->qr_content_data['end_date'])->toISOString() }}&body={{ urlencode($qrCode->qr_content_data['description'] ?? '') }}&location={{ urlencode($qrCode->qr_content_data['location'] ?? '') }}"
                                   target="_blank" class="btn btn-outline-info">
                                    <i class="bi bi-microsoft me-2"></i>Outlook
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection