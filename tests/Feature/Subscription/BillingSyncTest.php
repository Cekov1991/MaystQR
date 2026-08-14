<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\BillingAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.agentaos.key', 'sk_test_key');
        Http::preventStrayRequests();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function fakeSubscriptions(array $items): void
    {
        Http::fake([
            '*/gateway/subscriptions*' => Http::response([
                'items' => $items,
                'total' => count($items),
                'hasMore' => false,
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function remoteSubscription(string $email, string $periodEnd, string $status = 'active'): array
    {
        return [
            'id' => 'sub_remote_1',
            'customerEmail' => $email,
            'customerName' => 'Test Buyer',
            'planName' => 'MaystQR Yearly',
            'billingInterval' => 'year',
            'status' => $status,
            'unitAmountMinor' => 2700,
            'currency' => 'USD',
            'currentPeriodEnd' => $periodEnd,
            'stripeSubscriptionId' => 'sub_stripe_1',
        ];
    }

    public function test_sync_matches_by_stored_id_and_extends_entitlement(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_remote_1',
            'status' => 'active',
        ]);

        $periodEnd = now()->addYear();
        $this->fakeSubscriptions([
            $this->remoteSubscription($user->email, $periodEnd->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $user->refresh();
        $subscription->refresh();

        $this->assertSame(2700, $subscription->unit_amount_minor);
        $this->assertSame(27.0, $subscription->amount());
        $this->assertTrue($user->isSubscribed());
        // Period end plus the seven-day grace.
        $this->assertEqualsWithDelta(
            $periodEnd->copy()->addDays(7)->timestamp,
            $user->entitled_until->timestamp,
            5,
        );
    }

    public function test_sync_falls_back_to_email_for_a_row_that_never_got_an_id(): void
    {
        $user = User::factory()->create(['email' => 'Buyer@Example.com']);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'checkout_session_id' => 'sess_1',
            'status' => 'active',
        ]);

        // AgentaOS reports the address in a different case.
        $this->fakeSubscriptions([
            $this->remoteSubscription('buyer@example.com', now()->addYear()->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertSame('sub_remote_1', $subscription->fresh()->agentaos_subscription_id);
    }

    public function test_sync_never_shortens_entitlement(): void
    {
        $user = User::factory()->create();
        $user->entitled_until = now()->addYears(2);
        $user->save();
        $farFuture = $user->entitled_until;

        Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_remote_1',
            'status' => 'active',
        ]);

        // AgentaOS reports a period ending much sooner than what we hold.
        $this->fakeSubscriptions([
            $this->remoteSubscription($user->email, now()->addMonth()->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertTrue($farFuture->equalTo($user->fresh()->entitled_until));
    }

    public function test_a_past_due_subscription_does_not_revoke_access(): void
    {
        $user = User::factory()->create();
        $user->entitled_until = now()->addMonths(2);
        $user->save();
        $before = $user->entitled_until;

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_remote_1',
            'status' => 'active',
        ]);

        $this->fakeSubscriptions([
            $this->remoteSubscription($user->email, now()->addWeek()->toIso8601String(), 'past_due'),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertSame('past_due', $subscription->fresh()->status);
        $this->assertTrue($subscription->fresh()->isPastDue());
        $this->assertTrue($before->equalTo($user->fresh()->entitled_until));
        $this->assertTrue($user->fresh()->isEntitled());
    }

    public function test_a_cancelled_subscription_keeps_access_until_the_period_ends(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_remote_1',
            'status' => 'active',
        ]);

        $periodEnd = now()->addMonths(3);
        $this->fakeSubscriptions([
            $this->remoteSubscription($user->email, $periodEnd->toIso8601String(), 'canceled'),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertFalse($subscription->fresh()->isLive());
        $this->assertTrue($user->fresh()->isEntitled());

        // ...and lapses naturally once the paid period plus grace runs out.
        $this->travelTo($periodEnd->copy()->addDays(8));
        $this->assertTrue($user->fresh()->isLapsed());
    }

    public function test_an_api_failure_aborts_without_revoking_anything(): void
    {
        $user = User::factory()->create();
        $user->entitled_until = now()->addYear();
        $user->save();
        $before = $user->entitled_until;

        Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_remote_1',
            'status' => 'active',
        ]);

        Http::fake([
            '*/gateway/subscriptions*' => Http::response(
                ['statusCode' => 500, 'message' => 'Internal Server Error'],
                500,
            ),
        ]);

        $this->artisan('billing:sync')->assertFailed();

        $this->assertTrue($before->equalTo($user->fresh()->entitled_until));
        $this->assertTrue($user->fresh()->isEntitled());
    }

    public function test_a_remote_subscription_with_no_local_row_is_skipped(): void
    {
        User::factory()->create(['email' => 'someone@example.com']);

        $this->fakeSubscriptions([
            $this->remoteSubscription('stranger@example.com', now()->addYear()->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_sync_refuses_to_run_without_an_api_key(): void
    {
        config()->set('services.agentaos.key', null);

        $this->artisan('billing:sync')->assertFailed();
    }

    public function test_a_subscription_agentaos_no_longer_lists_is_closed(): void
    {
        $user = User::factory()->create();
        $user->entitled_until = now()->addMonths(3);
        $user->save();
        $before = $user->entitled_until;

        $vanished = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_gone',
            'status' => 'active',
        ]);

        // AgentaOS lists a different subscription, and never mentions sub_gone.
        $this->fakeSubscriptions([
            $this->remoteSubscription('stranger@example.com', now()->addYear()->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertFalse($vanished->fresh()->isLive());

        // Closing the row must not cost the customer the period they paid for.
        $this->assertTrue($before->equalTo($user->fresh()->entitled_until));
        $this->assertTrue($user->fresh()->isEntitled());
    }

    public function test_an_empty_listing_closes_nothing(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_remote_1',
            'status' => 'active',
        ]);

        // Far likelier a broken key than every subscription vanishing at once.
        $this->fakeSubscriptions([]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertTrue($subscription->fresh()->isLive());
    }

    public function test_an_aborted_run_closes_nothing(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_remote_1',
            'status' => 'active',
        ]);

        Http::fake([
            '*/gateway/subscriptions*' => Http::response(['message' => 'Bad Gateway'], 502),
        ]);

        $this->artisan('billing:sync')->assertFailed();

        $this->assertTrue($subscription->fresh()->isLive());
    }

    public function test_a_row_still_awaiting_its_remote_id_is_never_closed(): void
    {
        $user = User::factory()->create(['email' => 'fresh@example.com']);

        // A payment whose resolve job has not stitched it up yet.
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'checkout_session_id' => 'sess_fresh',
            'status' => 'active',
        ]);

        $this->fakeSubscriptions([
            $this->remoteSubscription('stranger@example.com', now()->addYear()->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertTrue($subscription->fresh()->isLive());
    }

    public function test_a_mass_disappearance_is_alerted_instead_of_applied(): void
    {
        Notification::fake();
        config()->set('subscription.alert_email', 'ops@easyqr.test');
        config()->set('subscription.max_missing_per_sync', 2);

        $subscriptions = collect(range(1, 3))->map(fn (int $index) => Subscription::create([
            'user_id' => User::factory()->create()->id,
            'agentaos_subscription_id' => "sub_gone_{$index}",
            'status' => 'active',
        ]));

        $this->fakeSubscriptions([
            $this->remoteSubscription('stranger@example.com', now()->addYear()->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        foreach ($subscriptions as $subscription) {
            $this->assertTrue($subscription->fresh()->isLive());
        }

        Notification::assertSentOnDemand(BillingAlert::class);
    }

    public function test_an_already_dead_row_is_left_alone(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_gone',
            'status' => 'unpaid',
        ]);

        $this->fakeSubscriptions([
            $this->remoteSubscription('stranger@example.com', now()->addYear()->toIso8601String()),
        ]);

        $this->artisan('billing:sync')->assertSuccessful();

        $this->assertSame('unpaid', $subscription->fresh()->status);
    }
}
