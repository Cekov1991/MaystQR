<?php

use App\Http\Controllers\AgentaOsWebhookController;
use App\Http\Controllers\InstantQrController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeRedirectController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InstantQrController::class, 'index'])->name('welcome');

Route::post('/qr/instant', [InstantQrController::class, 'generate'])
    ->middleware('throttle:20,1')
    ->name('qr.instant');

// QR Code routes
// Scans are public and a popular code is legitimately hit by many people, so the
// ceiling is per-IP and generous. It exists to stop one client looping a URL,
// which writes a scan row every time.
Route::get('/q/{shortUrl}', [QrCodeRedirectController::class, 'redirect'])
    ->middleware('throttle:60,1')
    ->name('qr.redirect');

/**
 * The Breeze dashboard was the untouched scaffold placeholder. The product is
 * the Filament panel, so this redirects there. It keeps its name because
 * Breeze's login and registration controllers still send users to
 * route('dashboard'), and the panel gates access itself.
 */
Route::get('/dashboard', fn () => redirect()->route('filament.admin.pages.dashboard'))
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Billing
    Route::post('/billing/subscribe', [SubscriptionController::class, 'checkout'])
        ->middleware('throttle:10,1')
        ->name('billing.subscribe');
    Route::get('/billing/success', [SubscriptionController::class, 'success'])->name('billing.success');
    Route::get('/billing/cancel', [SubscriptionController::class, 'cancel'])->name('billing.cancel');
});

// Signed by AgentaOS, not by a session. CSRF exemption lives in bootstrap/app.php.
Route::post('/webhooks/agentaos', AgentaOsWebhookController::class)->name('webhooks.agentaos');

/**
 * AgentaOS is merchant of record and reviews the site before approving it for
 * live payments, which includes checking that the price is reachable without
 * registering. It previously appeared only in clause 5 of the Terms and on the
 * billing page behind the login.
 */
Route::view('pricing', 'pricing')->name('pricing');

Route::view('terms-and-conditions', 'terms-and-conditions');

Route::view('privacy-policy', 'privacy-policy');

Route::view('refund-policy', 'refund-policy');

Route::get('cookies/accept', function () {
    return redirect()->back()->cookie('cookie_consent', 'accepted', 525600); // in minutes (1 year)
})->name('cookies.accept');

Route::get('cookies/decline', function () {
    return redirect()->back()->cookie('cookie_consent', 'declined', 525600); // in minutes (1 year)
})->name('cookies.reject');

require __DIR__.'/auth.php';
