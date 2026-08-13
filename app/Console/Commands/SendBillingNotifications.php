<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RenewalPaymentFailed;
use App\Notifications\TrialEnded;
use App\Notifications\TrialEndingSoon;
use Illuminate\Console\Command;

/**
 * Sends the three lifecycle emails.
 *
 * Deliberately separate from billing:sync: the trial is entirely ours, so
 * trial warnings must keep going out even when AgentaOS is unreachable or not
 * yet configured.
 */
class SendBillingNotifications extends Command
{
    protected $signature = 'billing:notify';

    protected $description = 'Send trial and subscription lifecycle emails';

    /** How many days before the trial ends the warning goes out. */
    private const WARN_DAYS_BEFORE = 2;

    public function handle(): int
    {
        $this->components->info(sprintf('Trial ending: %d sent.', $this->sendTrialEndingSoon()));
        $this->components->info(sprintf('Access ended: %d sent.', $this->sendAccessEnded()));
        $this->components->info(sprintf('Renewal failed: %d sent.', $this->sendRenewalFailed()));

        return self::SUCCESS;
    }

    private function sendTrialEndingSoon(): int
    {
        $users = User::query()
            ->whereNull('trial_ending_notified_at')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(self::WARN_DAYS_BEFORE)])
            // Anyone who already paid has nothing to act on.
            ->whereDoesntHave('subscriptions', fn ($query) => $query->live())
            ->get();

        foreach ($users as $user) {
            $user->notify(new TrialEndingSoon);
            $user->forceFill(['trial_ending_notified_at' => now()])->save();
        }

        return $users->count();
    }

    private function sendAccessEnded(): int
    {
        $users = User::query()
            ->whereNull('access_ended_notified_at')
            ->whereNotNull('entitled_until')
            ->where('entitled_until', '<=', now())
            ->get();

        foreach ($users as $user) {
            $user->notify(new TrialEnded);
            $user->forceFill(['access_ended_notified_at' => now()])->save();
        }

        return $users->count();
    }

    private function sendRenewalFailed(): int
    {
        $subscriptions = Subscription::query()
            ->where('status', 'past_due')
            ->whereNull('past_due_notified_at')
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->user === null) {
                continue;
            }

            $subscription->user->notify(new RenewalPaymentFailed($subscription));
            $subscription->forceFill(['past_due_notified_at' => now()])->save();
        }

        // A recovered subscription is armed again, so a future failure is not
        // silently swallowed by a stale timestamp.
        Subscription::query()
            ->whereNotNull('past_due_notified_at')
            ->where('status', '!=', 'past_due')
            ->update(['past_due_notified_at' => null]);

        return $subscriptions->count();
    }
}
