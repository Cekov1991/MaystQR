<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Services\IpGeolocationService;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\DB;

class QrCodeRedirectController extends Controller
{
    public function redirect($shortUrl, IpGeolocationService $geolocation)
    {
        $qrCode = QrCode::where('short_url', $shortUrl)->firstOrFail();

        // Check if QR code is expired (for dynamic QR codes)
        if ($qrCode->isExpired()) {
            return redirect()->route('qr.expired', $qrCode->short_url);
        }

        // Increment scan count and log the scan
        DB::transaction(function () use ($qrCode, $geolocation) {
            $qrCode->increment('scan_count');

            // Get device information
            $agent = new Agent();


            $location = $geolocation->locate(request()->ip());

            // Log the scan
            $qrCode->scans()->create([
                'scanned_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
                'device' => $agent->device(),
                'os' => $agent->platform(),
                'browser' => $agent->browser(),
                'country' => $location['country_code'],
                'city' => $location['city'],
            ]);
        });

        return redirect()->away($qrCode->destination_url);
    }
}
