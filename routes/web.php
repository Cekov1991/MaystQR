<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeRedirectController;
use App\Http\Controllers\QrCodeExpiredController;
use App\Http\Controllers\PaddleController;
use App\Models\QrCodePackage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $packages = QrCodePackage::active()->orderBy('duration_months')->get();
    return view('welcome', compact('packages'));
});

// QR Code routes
Route::get('/q/{shortUrl}', [QrCodeRedirectController::class, 'redirect'])
    ->name('qr.redirect');

Route::get('/qr/{shortUrl}/expired', [QrCodeExpiredController::class, 'show'])
    ->name('qr.expired');

Route::post('/qr/{shortUrl}/extend', [QrCodeExpiredController::class, 'extend'])
    ->middleware('auth')
    ->name('qr.extend');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Paddle webhook (no auth middleware - called by Paddle servers)
Route::post('/paddle/webhook', [PaddleController::class, 'webhook'])->name('paddle.webhook');

require __DIR__.'/auth.php';