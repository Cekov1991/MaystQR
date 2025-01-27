<?php

namespace App\Observers;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        Log::info('New subscription created', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id
        ]);
    }

    public function updated(Subscription $subscription): void
    {
        if ($subscription->wasChanged('status')) {
            Log::info('Subscription status changed', [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'old_status' => $subscription->getOriginal('status'),
                'new_status' => $subscription->status
            ]);
        }
    }

    public function deleted(Subscription $subscription): void
    {
        Log::info('Subscription deleted', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id
        ]);
    }
}
