<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class QrCodeRedirectController extends Controller
{
    /**
     * Cloudflare's placeholders for an country it could not determine and for
     * traffic arriving over Tor. Neither is a country, so neither is stored.
     */
    private const UNKNOWN_COUNTRIES = ['XX', 'T1'];

    public function redirect(string $shortUrl): View|RedirectResponse
    {
        $qrCode = QrCode::with('user')->where('short_url', $shortUrl)->firstOrFail();

        // A dynamic code resolves only while its owner holds an entitlement.
        // Static codes never reach this controller — their image encodes the
        // destination directly — so they are unaffected by billing state.
        if ($qrCode->isDynamic() && ! $qrCode->user?->isEntitled()) {
            $this->recordScan($qrCode, blocked: true);

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

        $this->recordScan($qrCode, blocked: false);

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
     *
     * Nothing in here may reach off the server. This used to call a paid
     * geolocation API inside this transaction, which meant a provider outage or
     * an exhausted quota stopped every dynamic code from resolving, and a slow
     * reply held a row lock on qr_codes for the length of the request.
     */
    private function recordScan(QrCode $qrCode, bool $blocked): void
    {
        DB::transaction(function () use ($qrCode, $blocked) {
            if (! $blocked) {
                $qrCode->increment('scan_count');
            }

            $agent = new Agent;

            $qrCode->scans()->create([
                'scanned_at' => now(),
                'blocked' => $blocked,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
                'device' => $agent->device(),
                'os' => $agent->platform(),
                'browser' => $agent->browser(),
                'country' => $this->country(),
            ]);
        });
    }

    /**
     * Cloudflare resolves the country for us and sends it on every request, so
     * there is no lookup to make, nothing to cache and no quota to exhaust.
     *
     * Returns null unless the header holds a plausible ISO 3166-1 alpha-2 code:
     * the header is absent locally and in tests, and requests that never passed
     * through Cloudflare can put anything they like in it.
     */
    private function country(): ?string
    {
        $country = strtoupper(trim((string) request()->header('CF-IPCountry')));

        if (! preg_match('/^[A-Z]{2}$/', $country) || in_array($country, self::UNKNOWN_COUNTRIES, true)) {
            return null;
        }

        return $country;
    }
}
