@extends('layouts.qr')

@section('title', 'Contact Card - {{ $qrCode->name }}')
@section('description', 'Save contact information')

@section('content')
<section class="hero d-flex align-items-center" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <!-- Contact Icon -->
                        <div class="mb-4">
                            <i class="bi bi-person-vcard text-primary" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Main Message -->
                        <h1 class="h2 text-primary mb-3">Contact Information</h1>
                        <h3 class="h4 mb-4">{{ $qrCode->qr_content_data['first_name'] }} {{ $qrCode->qr_content_data['last_name'] }}</h3>

                        <!-- Contact Details -->
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="bg-light p-4 rounded">
                                    @if(!empty($qrCode->qr_content_data['organization']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>Company:</strong></div>
                                            <div class="col-sm-9">{{ $qrCode->qr_content_data['organization'] }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['title']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>Title:</strong></div>
                                            <div class="col-sm-9">{{ $qrCode->qr_content_data['title'] }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['phone']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>Phone:</strong></div>
                                            <div class="col-sm-9">
                                                <a href="tel:{{ $qrCode->qr_content_data['phone'] }}" class="text-decoration-none">
                                                    {{ $qrCode->qr_content_data['phone'] }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['email']))
                                        <div class="row text-start mb-2">
                                            <div class="col-sm-3"><strong>Email:</strong></div>
                                            <div class="col-sm-9">
                                                <a href="mailto:{{ $qrCode->qr_content_data['email'] }}" class="text-decoration-none">
                                                    {{ $qrCode->qr_content_data['email'] }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($qrCode->qr_content_data['website']))
                                        <div class="row text-start">
                                            <div class="col-sm-3"><strong>Website:</strong></div>
                                            <div class="col-sm-9">
                                                <a href="{{ $qrCode->qr_content_data['website'] }}" target="_blank" class="text-decoration-none">
                                                    {{ $qrCode->qr_content_data['website'] }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4">
                            <a href="{{ $qrCode->formated_content }}" class="btn btn-primary btn-lg mb-2">
                                <i class="bi bi-download me-2"></i>Save to Contacts
                            </a>
                        </div>

                        <!-- Individual Action Buttons -->
                        <div class="mt-3">
                            @if(!empty($qrCode->qr_content_data['phone']))
                                <a href="tel:{{ $qrCode->qr_content_data['phone'] }}" class="btn btn-outline-success me-2 mb-2">
                                    <i class="bi bi-telephone me-2"></i>Call
                                </a>
                            @endif
                            @if(!empty($qrCode->qr_content_data['email']))
                                <a href="mailto:{{ $qrCode->qr_content_data['email'] }}" class="btn btn-outline-primary me-2 mb-2">
                                    <i class="bi bi-envelope me-2"></i>Email
                                </a>
                            @endif
                            @if(!empty($qrCode->qr_content_data['website']))
                                <a href="{{ $qrCode->qr_content_data['website'] }}" target="_blank" class="btn btn-outline-info mb-2">
                                    <i class="bi bi-globe me-2"></i>Website
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection