<?php


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeRedirectController;
use App\Http\Controllers\QrCodeExpiredController;
use App\Http\Controllers\QrCodePackageController;
use App\Http\Controllers\PayPalController;
use App\Models\QrCodePackage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $packages = QrCodePackage::active()->orderBy('duration_months')->get();
    return view('welcome', compact('packages'));
})->name('welcome');

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

// PayPal routes (existing subscription system)
Route::middleware(['auth'])->group(function () {
    Route::get('/paypal/connect', [PayPalController::class, 'connect'])->name('paypal.connect');
    Route::get('/paypal/success', [PayPalController::class, 'success'])->name('paypal.success');
    Route::get('/paypal/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');
});

// QR Code Package Purchase routes
// Route::middleware(['auth'])->group(function () {
//     Route::get('/qr/{qrCode}/package/{package}', [QrCodePackageController::class, 'show'])
//         ->name('qr.package.purchase');

//     Route::post('/qr/{qrCode}/package/{package}/buy', [QrCodePackageController::class, 'purchase'])
//         ->name('qr.package.buy');

//     Route::get('/qr/package/success', [QrCodePackageController::class, 'success'])
//         ->name('qr.package.success');

//     Route::get('/qr/package/cancel', [QrCodePackageController::class, 'cancel'])
//         ->name('qr.package.cancel');
// });

Route::view('terms-and-conditions', 'terms-and-conditions');

Route::view('privacy-policy', 'privacy-policy');

// Route::view('refund-policy', 'refund-policy');

Route::get('cookies/accept', function() {
    return redirect()->back()->cookie('cookie_consent', 'accepted', 525600); // in minutes (1 year)
})->name('cookies.accept');

Route::get('cookies/decline', function() {
    return redirect()->back()->cookie('cookie_consent', 'declined', 525600); // in minutes (1 year)
})->name('cookies.reject');

require __DIR__.'/auth.php';







