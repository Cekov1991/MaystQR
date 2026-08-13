<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Services\IpGeolocationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class QrCodeRedirectController extends Controller
{
    public function redirect(string $shortUrl, IpGeolocationService $geolocation): View|RedirectResponse
    {
        $qrCode = QrCode::with('user')->where('short_url', $shortUrl)->firstOrFail();

        // A dynamic code resolves only while its owner holds an entitlement.
        // Static codes never reach this controller — their image encodes the
        // destination directly — so they are unaffected by billing state.
        if ($qrCode->isDynamic() && ! $qrCode->user?->isEntitled()) {
            $this->recordScan($qrCode, $geolocation, blocked: true);

            // The scanner is a stranger who cannot pay someone else's
            // subscription, so they see nothing about the owner or the
            // destination. Only the owner gets the reactivation prompt.
            $viewerIsOwner = Auth::check() && Auth::id() === $qrCode->user_id;

            return view('qr.inactive', [
                'qrCode' => $qrCode,
                'viewerIsOwner' => $viewerIsOwner,
                'offlineCodeCount' => $viewerIsOwner
                    ? $qrCode->user->qrCodes()->where('type', 'dynamic')->count()
                    : 0,
                'missedScanCount' => $viewerIsOwner
                    ? $qrCode->scans()->blocked()->count()
                    : 0,
            ]);
        }

        $this->recordScan($qrCode, $geolocation, blocked: false);

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

    /**
     * A blocked scan is logged but never counted: the code did not resolve, so
     * it does not belong in `scan_count`. It stays queryable so the owner can
     * be shown what their inactive subscription cost them.
     */
    private function recordScan(QrCode $qrCode, IpGeolocationService $geolocation, bool $blocked): void
    {
        DB::transaction(function () use ($qrCode, $geolocation, $blocked) {
            if (! $blocked) {
                $qrCode->increment('scan_count');
            }

            $agent = new Agent;

            $location = $geolocation->locate(config('app.env') === 'local' ? '46.217.223.14' : request()->ip());

            $qrCode->scans()->create([
                'scanned_at' => now(),
                'blocked' => $blocked,
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
    }
}
