<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RenewalPaymentFailed;
use App\Notifications\TrialEnded;
use App\Notifications\TrialEndingSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_the_trial_warning_goes_out_two_days_before_the_end(): void
    {
        $user = User::factory()->create();

        $this->travel(5)->days();
        $this->artisan('billing:notify')->assertSuccessful();

        Notification::assertSentTo($user, TrialEndingSoon::class);
    }

    public function test_the_trial_warning_is_not_sent_too_early(): void
    {
        $user = User::factory()->create();

        $this->artisan('billing:notify')->assertSuccessful();

        Notification::assertNotSentTo($user, TrialEndingSoon::class);
    }

    public function test_the_trial_warning_is_sent_only_once(): void
    {
        $user = User::factory()->create();

        $this->travel(5)->days();
        $this->artisan('billing:notify');
        $this->artisan('billing:notify');

        Notification::assertSentToTimes($user, TrialEndingSoon::class, 1);
    }

    public function test_a_subscriber_gets_no_trial_warning(): void
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_1',
            'status' => 'active',
        ]);

        $this->travel(5)->days();
        $this->artisan('billing:notify')->assertSuccessful();

        Notification::assertNotSentTo($user, TrialEndingSoon::class);
    }

    public function test_the_access_ended_email_goes_out_once_entitlement_lapses(): void
    {
        $user = User::factory()->create();

        $this->travel(8)->days();
        $this->artisan('billing:notify')->assertSuccessful();

        Notification::assertSentTo($user, TrialEnded::class);
    }

    public function test_the_access_ended_email_is_sent_only_once(): void
    {
        $user = User::factory()->create();

        $this->travel(8)->days();
        $this->artisan('billing:notify');
        $this->artisan('billing:notify');

        Notification::assertSentToTimes($user, TrialEnded::class, 1);
    }

    public function test_an_entitled_user_is_not_told_their_access_ended(): void
    {
        $user = User::factory()->create();

        $this->artisan('billing:notify')->assertSuccessful();

        Notification::assertNotSentTo($user, TrialEnded::class);
    }

    public function test_resubscribing_rearms_the_access_ended_email(): void
    {
        $user = User::factory()->create();

        $this->travel(8)->days();
        $this->artisan('billing:notify');
        Notification::assertSentToTimes($user, TrialEnded::class, 1);

        // They pay, which restores entitlement...
        $user->refresh()->grantEntitlementThrough(now()->addYear());
        $this->assertNull($user->fresh()->access_ended_notified_at);

        // ...and lapse again a year later.
        $this->travel(400)->days();
        $this->artisan('billing:notify');

        Notification::assertSentToTimes($user, TrialEnded::class, 2);
    }

    public function test_a_past_due_subscription_warns_the_owner(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_1',
            'status' => 'past_due',
        ]);

        $this->artisan('billing:notify')->assertSuccessful();

        Notification::assertSentTo($user, RenewalPaymentFailed::class);
        $this->assertNotNull($subscription->fresh()->past_due_notified_at);
    }

    public function test_the_past_due_warning_is_sent_only_once(): void
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_1',
            'status' => 'past_due',
        ]);

        $this->artisan('billing:notify');
        $this->artisan('billing:notify');

        Notification::assertSentToTimes($user, RenewalPaymentFailed::class, 1);
    }

    public function test_a_recovered_subscription_is_rearmed_for_a_future_failure(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'agentaos_subscription_id' => 'sub_1',
            'status' => 'past_due',
        ]);

        $this->artisan('billing:notify');
        $this->assertNotNull($subscription->fresh()->past_due_notified_at);

        // The retry succeeds.
        $subscription->update(['status' => 'active']);
        $this->artisan('billing:notify');
        $this->assertNull($subscription->fresh()->past_due_notified_at);

        // A later failure warns again.
        $subscription->update(['status' => 'past_due']);
        $this->artisan('billing:notify');

        Notification::assertSentToTimes($user, RenewalPaymentFailed::class, 2);
    }
}
