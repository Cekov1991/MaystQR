<?php

namespace Tests\Unit;

use App\Enums\BillingInterval;
use Tests\TestCase;
use ValueError;

class BillingIntervalTest extends TestCase
{
    public function test_a_period_ends_one_interval_after_it_starts(): void
    {
        $start = now()->setDate(2026, 1, 31)->startOfDay();

        $this->assertTrue(
            BillingInterval::Year->endFrom($start)->equalTo($start->copy()->addYear()),
        );

        $this->assertTrue(
            BillingInterval::Month->endFrom($start)->equalTo($start->copy()->addMonth()),
        );
    }

    public function test_the_start_is_not_mutated(): void
    {
        $start = now()->startOfDay();
        $before = $start->copy();

        BillingInterval::Year->endFrom($start);

        $this->assertTrue($start->equalTo($before));
    }

    public function test_the_configured_interval_is_the_one_agentaos_is_sent(): void
    {
        config()->set('subscription.billing_interval', 'year');

        $this->assertSame(BillingInterval::Year, BillingInterval::configured());
        $this->assertSame('year', BillingInterval::configured()->value);
    }

    /**
     * Silently falling back to a year would bill one period and grant another,
     * so an interval this enum does not name has to stop the call it is part of.
     */
    public function test_an_unsupported_interval_throws_rather_than_assuming_a_year(): void
    {
        config()->set('subscription.billing_interval', 'fortnight');

        $this->expectException(ValueError::class);

        BillingInterval::configured();
    }
}
