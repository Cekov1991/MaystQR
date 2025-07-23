<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrCodePackage;
use Illuminate\Http\Request;

class QrCodeExpiredController extends Controller
{
    public function show($shortUrl)
    {
        $qrCode = QrCode::where('short_url', $shortUrl)->firstOrFail();

        // If QR code is not expired, redirect to normal flow
        if (!$qrCode->isExpired()) {
            return redirect()->route('qr.redirect', $shortUrl);
        }

        $packages = QrCodePackage::active()->orderBy('duration_months')->get();

        return view('qr-expired', compact('qrCode', 'packages'));
    }

    public function extend(Request $request, $shortUrl)
    {
        $request->validate([
            'package_id' => 'required|exists:qr_code_packages,id',
        ]);

        $qrCode = QrCode::where('short_url', $shortUrl)->firstOrFail();

        // Check if user is the owner
        if (auth()->guest() || $qrCode->user_id !== auth()->id()) {
            return redirect()->route('login')->with('message', 'Please log in to extend your QR code.');
        }

        $package = QrCodePackage::findOrFail($request->package_id);

        return redirect()->route('qr.package.purchase', [
            'qrCode' => $qrCode->id,
            'package' => $package->id
        ]);
    }
}