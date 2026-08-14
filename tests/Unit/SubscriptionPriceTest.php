<?php

namespace Tests\Unit;

use App\Support\SubscriptionPrice;
use Tests\TestCase;

class SubscriptionPriceTest extends TestCase
{
    public function test_a_round_price_is_written_without_trailing_zeros(): void
    {
        config()->set('subscription.price', 27);
        config()->set('subscription.currency', 'USD');

        $this->assertSame('$27', SubscriptionPrice::formatted());
    }

    public function test_real_cents_are_kept(): void
    {
        config()->set('subscription.price', 27.5);
        config()->set('subscription.currency', 'USD');

        $this->assertSame('$27.50', SubscriptionPrice::formatted());
    }

    /**
     * The customer-facing price is quoted alongside the period, and the period
     * comes from the same enum AgentaOS is sent. Hardcoding "/year" beside a
     * configurable interval is how the two drift apart, which is the bug fixed
     * in 18f75cb for the entitlement grant.
     */
    public function test_the_period_follows_the_configured_billing_interval(): void
    {
        config()->set('subscription.price', 27);
        config()->set('subscription.currency', 'USD');

        config()->set('subscription.billing_interval', 'year');
        $this->assertSame('$27/year', SubscriptionPrice::perInterval());

        config()->set('subscription.billing_interval', 'month');
        $this->assertSame('$27/month', SubscriptionPrice::perInterval());
    }

    /**
     * A dollar sign in front of a non-USD amount is a misstatement of price, and
     * price is the one thing AgentaOS reviews us on as merchant of record.
     */
    public function test_a_non_usd_price_is_not_labelled_as_dollars(): void
    {
        config()->set('subscription.price', 25);
        config()->set('subscription.currency', 'EUR');

        $this->assertSame('25 EUR', SubscriptionPrice::formatted());
        $this->assertStringNotContainsString('$', SubscriptionPrice::formatted());
    }

    public function test_the_currency_comes_from_config(): void
    {
        config()->set('subscription.currency', 'USD');

        $this->assertSame('USD', SubscriptionPrice::currency());
    }
}
