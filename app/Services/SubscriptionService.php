<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Exception;

class SubscriptionService
{
    public function createSubscription(User $user): Subscription
    {
        // Create free tier subscription
        return Subscription::create([
            'user_id' => $user->id,
            'dynamic_qr_codes_limit' => 1,
            'monthly_scan_limit' => 1000,
            'has_advanced_analytics' => false,
            'has_custom_branding' => false,
            'current_price' => 0,
            'next_billing_date' => now()->addMonth(),
        ]);
    }

    public function addFeature(Subscription $subscription, string $featureKey, int|bool $value, float $price): void
    {
        // Update subscription based on feature
        switch ($featureKey) {
            case 'dynamic_qr_codes':
                $subscription->dynamic_qr_codes_limit = $value;
                break;
            case 'monthly_scans':
                $subscription->monthly_scan_limit = $value;
                break;
            case 'advanced_analytics':
                $subscription->has_advanced_analytics = $value;
                break;
            case 'custom_branding':
                $subscription->has_custom_branding = $value;
                break;
            default:
                throw new Exception('Invalid feature key');
        }

        // Add feature and update price
        $subscription->addFeature($featureKey, $value, $price);
        $subscription->save();
    }

    public function updatePayPalSubscription(Subscription $subscription, string $paypalSubscriptionId): void
    {
        $subscription->update([
            'paypal_subscription_id' => $paypalSubscriptionId,
            'next_billing_date' => now()->addMonth(),
        ]);
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'cancelled',
        ]);

        // Here you would also cancel the PayPal subscription
        // Implementation depends on your PayPal integration
    }

    public function reactivateSubscription(Subscription $subscription): void
    {
        if ($subscription->status !== 'cancelled') {
            throw new Exception('Only cancelled subscriptions can be reactivated');
        }

        $subscription->update([
            'status' => 'active',
            'next_billing_date' => now()->addMonth(),
        ]);
    }
}
