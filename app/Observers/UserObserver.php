<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Grant the free trial at registration.
     *
     * Both columns are stamped: `trial_ends_at` is the permanent record of the
     * trial window, `entitled_until` is the live gate that a payment later
     * extends. They start equal and diverge the moment the user subscribes.
     */
    public function creating(User $user): void
    {
        if ($user->trial_ends_at === null) {
            $user->trial_ends_at = now()->addDays((int) config('subscription.trial_days'));
        }

        if ($user->entitled_until === null) {
            $user->entitled_until = $user->trial_ends_at;
        }
    }
}
