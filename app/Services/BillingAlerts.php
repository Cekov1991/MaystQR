<?php

namespace App\Services;

use App\Notifications\BillingAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Escalates billing failures that would otherwise reach nothing but the log.
 *
 * Each call site marks a point where a payment can be taken without access
 * being granted, or where the reconciliation safety net has stopped running —
 * the failures a customer notices before we do.
 */
class BillingAlerts
{
    /**
     * Logs the failure, then emails it at most once per throttle window.
     *
     * @param  string  $kind  Throttling bucket, so one recurring fault cannot bury the inbox.
     * @param  array<string, mixed>  $context
     */
    public static function raise(string $kind, string $summary, array $context = []): void
    {
        Log::error($summary, $context + ['alert' => $kind]);

        $recipient = config('subscription.alert_email');

        if (blank($recipient) || ! self::isFirstInWindow($kind)) {
            return;
        }

        try {
            Notification::route('mail', $recipient)
                ->notify(new BillingAlert($summary, $context));
        } catch (Throwable $exception) {
            // Failing to alert must never break the operation being reported on:
            // entitlement has usually just been granted and must stand.
            Log::error('Could not deliver a billing alert.', [
                'alert' => $kind,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private static function isFirstInWindow(string $kind): bool
    {
        $minutes = (int) config('subscription.alert_throttle_minutes');

        if ($minutes < 1) {
            return true;
        }

        return Cache::add("billing-alert:{$kind}", true, now()->addMinutes($minutes));
    }
}
