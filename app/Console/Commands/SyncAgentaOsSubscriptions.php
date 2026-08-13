<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\AgentaOS\AgentaOsClient;
use App\Services\AgentaOS\AgentaOsException;
use App\Services\BillingAlerts;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Reconciles local subscription state with AgentaOS.
 *
 * AgentaOS emits no subscription lifecycle webhooks — the only events are
 * checkout completion and outbound sends. Renewals, cancellations and failed
 * cards are therefore discoverable only by polling, which is what this does.
 *
 * It may extend `entitled_until` or leave it alone. It never shortens it: a
 * bad API day must not darken a paying customer's printed QR codes.
 */
class SyncAgentaOsSubscriptions extends Command
{
    protected $signature = 'billing:sync';

    protected $description = 'Reconcile local subscription state and entitlement with AgentaOS';

    public function handle(AgentaOsClient $agentaOs): int
    {
        if (blank(config('services.agentaos.key'))) {
            $this->components->error('AGENTAOS_API_KEY is not configured.');

            return self::FAILURE;
        }

        $seen = 0;
        $matched = 0;
        $extended = 0;

        /** @var array<int, string> $seenIds */
        $seenIds = [];

        try {
            foreach ($agentaOs->eachSubscription() as $remote) {
                $seen++;

                if (filled($remote['id'] ?? null)) {
                    $seenIds[] = (string) $remote['id'];
                }

                $subscription = $this->findLocal($remote);

                if ($subscription === null) {
                    continue;
                }

                $matched++;

                $periodEnd = filled($remote['currentPeriodEnd'] ?? null)
                    ? Carbon::parse($remote['currentPeriodEnd'])
                    : null;

                $subscription->update([
                    'agentaos_subscription_id' => $remote['id'],
                    'status' => $remote['status'] ?? $subscription->status,
                    'current_period_end' => $periodEnd ?? $subscription->current_period_end,
                    'unit_amount_minor' => $remote['unitAmountMinor'] ?? $subscription->unit_amount_minor,
                    'currency' => $remote['currency'] ?? $subscription->currency,
                ]);

                if ($subscription->user?->grantEntitlementThrough($periodEnd)) {
                    $extended++;
                }
            }
        } catch (AgentaOsException $exception) {
            // Deliberately not a partial rollback: everything reconciled
            // before the failure is still correct, and nothing was revoked.
            BillingAlerts::raise(
                'sync-aborted',
                'billing:sync could not finish listing AgentaOS subscriptions. Renewals, cancellations and failed cards are only discoverable through this run, so state is now drifting.',
                [
                    'subscriptions_checked' => $seen,
                    'status' => $exception->status,
                    'request_id' => $exception->requestId,
                    'message' => $exception->getMessage(),
                ],
            );

            $this->components->error("Sync aborted after {$seen} subscriptions: {$exception->getMessage()}");

            return self::FAILURE;
        }

        // Only reachable when the listing completed: the catch above returns.
        $closed = $this->closeVanished($seenIds, $seen);

        $this->components->info("Checked {$seen} AgentaOS subscriptions, matched {$matched}, extended {$extended}, closed {$closed}.");

        return self::SUCCESS;
    }

    /**
     * Closes local rows for subscriptions AgentaOS has stopped listing.
     *
     * Without this a row whose remote counterpart is purged after cancellation
     * keeps `status` forever, so `User::activeSubscription()` goes on reporting
     * a subscription that no longer exists anywhere.
     *
     * Entitlement is deliberately untouched. `entitled_until` runs its course
     * either way, which is what makes this inference safe to make at all: being
     * wrong costs a misleading badge, never a customer's access.
     *
     * @param  array<int, string>  $seenIds
     * @param  int  $seen  How many subscriptions the run actually listed.
     */
    private function closeVanished(array $seenIds, int $seen): int
    {
        // An empty listing is far likelier to be an auth or API fault than
        // every subscription in the account disappearing at once.
        if ($seen === 0) {
            return 0;
        }

        // Rows still awaiting a remote id are exempt: absence there means the
        // payment has not been stitched up yet, not that anything was cancelled.
        $vanished = Subscription::query()
            ->live()
            ->whereNotNull('agentaos_subscription_id')
            ->whereNotIn('agentaos_subscription_id', $seenIds)
            ->get();

        if ($vanished->isEmpty()) {
            return 0;
        }

        $cap = (int) config('subscription.max_missing_per_sync');

        if ($cap > 0 && $vanished->count() > $cap) {
            BillingAlerts::raise(
                'subscriptions-missing-en-masse',
                "billing:sync found {$vanished->count()} live subscriptions missing from AgentaOS, past the safety cap of {$cap}. Nothing was closed — a listing that drops many subscriptions at once is more likely an API fault than mass cancellation.",
                [
                    'missing' => $vanished->count(),
                    'cap' => $cap,
                    'listed_by_agentaos' => $seen,
                ],
            );

            return 0;
        }

        foreach ($vanished as $subscription) {
            $subscription->update(['status' => 'canceled']);
        }

        return $vanished->count();
    }

    /**
     * Prefer the stored id. Fall back to email only for rows that never got
     * one — the case ResolveAgentaOsSubscription failed to complete.
     *
     * @param  array<string, mixed>  $remote
     */
    private function findLocal(array $remote): ?Subscription
    {
        $subscription = Subscription::where('agentaos_subscription_id', $remote['id'] ?? '')->first();

        if ($subscription !== null) {
            return $subscription;
        }

        $email = $remote['customerEmail'] ?? null;

        if (blank($email)) {
            return null;
        }

        return Subscription::awaitingRemoteId()
            ->whereHas('user', fn ($query) => $query->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))]))
            ->latest('id')
            ->first();
    }
}
