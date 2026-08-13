<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\AgentaOS\AgentaOsClient;
use App\Services\AgentaOS\AgentaOsException;
use App\Services\BillingAlerts;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Finds the AgentaOS subscription behind a paid checkout.
 *
 * The webhook does not carry a subscription id, and the Subscription resource
 * carries no metadata, so email is the only join between the two. This runs
 * once after payment; from then on the stored id is the key and email is never
 * consulted again — so a user changing their email later is harmless.
 */
class ResolveAgentaOsSubscription implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public function __construct(private readonly Subscription $subscription) {}

    /**
     * The subscription may not exist at AgentaOS the instant the webhook
     * lands, so back off and look again rather than giving up.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    public function handle(AgentaOsClient $agentaOs): void
    {
        $user = $this->subscription->user;

        if ($user === null) {
            return;
        }

        try {
            foreach ($agentaOs->eachSubscription() as $remote) {
                if (! $this->matches($remote, $user->email)) {
                    continue;
                }

                $periodEnd = filled($remote['currentPeriodEnd'] ?? null)
                    ? Carbon::parse($remote['currentPeriodEnd'])
                    : null;

                $this->subscription->update([
                    'agentaos_subscription_id' => $remote['id'],
                    'status' => $remote['status'] ?? 'active',
                    'current_period_end' => $periodEnd,
                    'unit_amount_minor' => $remote['unitAmountMinor'] ?? null,
                    'currency' => $remote['currency'] ?? $this->subscription->currency,
                ]);

                $user->grantEntitlementThrough($periodEnd);

                return;
            }
        } catch (AgentaOsException $exception) {
            Log::warning('Could not list AgentaOS subscriptions while resolving a payment.', [
                'subscription_id' => $this->subscription->getKey(),
                'status' => $exception->status,
                'request_id' => $exception->requestId,
            ]);

            $this->release(60);

            return;
        }

        Log::notice('No AgentaOS subscription matched this payment yet; will retry.', [
            'subscription_id' => $this->subscription->getKey(),
            'attempt' => $this->attempts(),
        ]);

        $this->release(60);
    }

    /**
     * Prefers the id the webhook handed us in metadata. Email is only consulted
     * for payments whose metadata carried no id — which matters because the
     * buyer can edit their address at the hosted checkout, and an edited
     * address makes the email match silently find nothing.
     *
     * @param  array<string, mixed>  $remote
     */
    private function matches(array $remote, string $email): bool
    {
        $knownId = $this->subscription->agentaos_subscription_id;

        if (filled($knownId)) {
            return ($remote['id'] ?? null) === $knownId;
        }

        return $this->matchesUserEmail($remote, $email);
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function matchesUserEmail(array $remote, string $email): bool
    {
        $customerEmail = $remote['customerEmail'] ?? null;

        return filled($customerEmail)
            && mb_strtolower(trim($customerEmail)) === mb_strtolower(trim($email));
    }

    /**
     * Entitlement was already granted provisionally, so exhausting the retries
     * costs visibility, not access.
     */
    public function failed(?\Throwable $exception): void
    {
        BillingAlerts::raise(
            'subscription-unresolved',
            'Gave up matching a paid AgentaOS checkout to a subscription. Access was granted on a provisional date that nothing will now correct except the daily sync.',
            [
                'subscription_id' => $this->subscription->getKey(),
                'user_email' => $this->subscription->user?->email,
                'exception' => $exception?->getMessage(),
            ],
        );
    }
}
