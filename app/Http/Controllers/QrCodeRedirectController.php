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

            // Get location information
            $location = $geolocation->locate(config('app.env') === 'local' ? '46.217.223.14' : request()->ip());

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

        // Handle different content types
        return match ($qrCode->qr_content_type) {
            'website' => redirect()->away($qrCode->qr_content_data['url'] ?? $qrCode->destination_url),
            'wifi' => view('qr.wifi', compact('qrCode')),
            'email' => view('qr.email', compact('qrCode')),
            'whatsapp' => view('qr.whatsapp', compact('qrCode')),
            'vcard' => view('qr.vcard', compact('qrCode')),
            'sms' => view('qr.sms', compact('qrCode')),
            'phone' => view('qr.phone', compact('qrCode')),
            'text' => view('qr.text', compact('qrCode')),
            'calendar' => view('qr.calendar', compact('qrCode')),
            'location' => view('qr.location', compact('qrCode')),
            default => redirect()->away($qrCode->destination_url),
        };
    }

    public function expired($shortUrl)
    {
        $qrCode = QrCode::where('short_url', $shortUrl)->firstOrFail();
        return view('qr.expired', compact('qrCode'));
    }
}
