<?php

namespace Tests\Feature\Subscription;

use App\Enums\AccountState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_grants_a_seven_day_trial(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->trial_ends_at);
        $this->assertEqualsWithDelta(
            now()->addDays(7)->timestamp,
            $user->trial_ends_at->timestamp,
            5,
        );
    }

    public function test_entitlement_starts_equal_to_the_trial_end(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->entitled_until->equalTo($user->trial_ends_at));
    }

    public function test_a_new_user_is_trialing(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isEntitled());
        $this->assertTrue($user->isTrialing());
        $this->assertFalse($user->isSubscribed());
        $this->assertFalse($user->isLapsed());
        $this->assertSame(AccountState::Trialing, $user->accountState());
    }

    public function test_the_account_lapses_once_the_trial_runs_out(): void
    {
        $user = User::factory()->create();

        $this->travel(8)->days();

        $user->refresh();

        $this->assertFalse($user->isEntitled());
        $this->assertTrue($user->isLapsed());
        $this->assertFalse($user->isTrialing());
        $this->assertSame(AccountState::Lapsed, $user->accountState());
    }

    public function test_entitlement_reaching_past_the_trial_reads_as_subscribed(): void
    {
        $user = User::factory()->create();

        $user->entitled_until = now()->addYear()->addDays(7);
        $user->save();

        $this->assertTrue($user->isSubscribed());
        $this->assertFalse($user->isTrialing());
        $this->assertSame(AccountState::Subscribed, $user->accountState());
    }

    public function test_a_subscriber_lapses_when_their_entitlement_clock_runs_out(): void
    {
        $user = User::factory()->create();
        $user->entitled_until = now()->addYear();
        $user->save();

        $this->travel(1)->year();
        $this->travel(1)->day();

        $user->refresh();

        $this->assertTrue($user->isLapsed());
        $this->assertFalse($user->isSubscribed());
        $this->assertSame(AccountState::Lapsed, $user->accountState());
    }

    public function test_missing_entitlement_columns_read_as_lapsed(): void
    {
        $user = User::factory()->create();

        $user->forceFill([
            'trial_ends_at' => null,
            'entitled_until' => null,
        ])->save();

        $this->assertTrue($user->fresh()->isLapsed());
    }

    public function test_trial_days_remaining_counts_down_and_floors_at_zero(): void
    {
        $user = User::factory()->create();

        $this->assertSame(7, $user->trialDaysRemaining());

        $this->travel(5)->days();
        $this->assertSame(2, $user->fresh()->trialDaysRemaining());

        $this->travel(10)->days();
        $this->assertSame(0, $user->fresh()->trialDaysRemaining());
    }

    public function test_an_explicit_trial_end_is_not_overwritten(): void
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(30),
        ]);

        $this->assertSame(30, $user->trialDaysRemaining());
    }

    public function test_lapsed_accounts_do_not_resolve_dynamic_codes(): void
    {
        $this->assertFalse(AccountState::Lapsed->resolvesDynamicCodes());
        $this->assertTrue(AccountState::Trialing->resolvesDynamicCodes());
        $this->assertTrue(AccountState::Subscribed->resolvesDynamicCodes());
    }
}
