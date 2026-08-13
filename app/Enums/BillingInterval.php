<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * How often a subscription is billed.
 *
 * The single source of truth for the interval. AgentaOS is told it when the
 * payment link is created, and the provisional entitlement granted on payment
 * runs one of these from the moment of payment.
 *
 * Those two were declared independently before: config named the interval while
 * GrantSubscriptionEntitlement hardcoded `addYear()`. They agreed, but nothing
 * made them agree — changing the config would have billed one period and
 * granted another, silently and in the customer's favour.
 *
 * Only Year is in use and verified against AgentaOS. Month is here so the
 * mapping is not a special case of one, but changing the interval is a product
 * decision beyond this enum: the price, the refund policy, the terms and the
 * billing copy all state a yearly period in words.
 */
enum BillingInterval: string
{
    case Month = 'month';
    case Year = 'year';

    /**
     * The configured interval, sent to AgentaOS verbatim as `billingInterval`.
     *
     * Throws on any value this enum does not name, so an unsupported interval
     * fails where it is used rather than quietly falling back to a year.
     */
    public static function configured(): self
    {
        return self::from((string) config('subscription.billing_interval'));
    }

    /**
     * The end of one billing period that begins at $start.
     */
    public function endFrom(CarbonInterface $start): CarbonInterface
    {
        return match ($this) {
            self::Month => $start->copy()->addMonth(),
            self::Year => $start->copy()->addYear(),
        };
    }
}
