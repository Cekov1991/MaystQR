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

// Previous marketing landing page, parked here until the new design is finalized
// (path must not be /landing — public/landing/ is the template asset directory and shadows the route)
Route::view('/landing-page', 'welcome')->name('landing');

// QR Code routes
Route::get('/q/{shortUrl}', [QrCodeRedirectController::class, 'redirect'])
    ->name('qr.redirect');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
