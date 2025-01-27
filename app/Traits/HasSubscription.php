<?php

namespace App\Traits;

use App\Models\Subscription;

trait HasSubscription
{
    public static function bootHasSubscription()
    {
        static::created(function ($user) {
            $user->createDefaultSubscription();
            $user->createFreeQrCode();
        });
    }

    public function createDefaultSubscription(): Subscription
    {
        return $this->subscription()->create([
            'dynamic_qr_codes_limit' => 1,
            'monthly_scan_limit' => 1000,
            'has_advanced_analytics' => false,
            'has_custom_branding' => false,
            'current_price' => 0,
            'status' => 'active',
            'next_billing_date' => now()->addMonth(),
        ]);
    }
}
