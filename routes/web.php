<?php

use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicationController;
use App\Models\Publication;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\QrCodeRedirectController;


Route::get('/q/{shortUrl}', [QrCodeRedirectController::class, 'redirect'])
    ->name('qr.redirect');

Route::get('/', [WelcomeController::class, 'index'])
    ->name('welcome');




