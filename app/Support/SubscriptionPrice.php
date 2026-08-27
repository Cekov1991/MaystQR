<?php

namespace App\Support;

use App\Enums\BillingInterval;

/**
 * The subscription price as the customer reads it.
 *
 * The same rtrim(rtrim(number_format())) chain was written out four times: on
 * the Filament billing page, in clause 5 of the Terms, and privately inside
 * each of the two trial notifications — which additionally hardcoded the
 * currency symbol and the word "year" around it. Nothing made those four
 * agree; they simply did.
 *
 * That is the shape of the bug fixed in 18f75cb, where config named the billing
 * interval and GrantSubscriptionEntitlement independently hardcoded addYear().
 * The interval here comes from BillingInterval, so the period a customer is
 * quoted and the period sent to AgentaOS cannot drift apart.
 */
class SubscriptionPrice
{
    public static function currency(): string
    {
        return (string) config('subscription.currency');
    }

    /**
     * "$27" — a whole amount drops its cents, because a round number reads as a
     * price and "$27.00" reads as a form field.
     *
     * Only an exactly-zero fraction is dropped. The four expressions this method
     * replaced all used rtrim(rtrim($formatted, '0'), '.'), which also eats a
     * significant trailing zero and renders 27.50 as "27.5" — a malformed price.
     * It never surfaced because the price has always been a whole number.
     *
     * The dollar sign is not decoration either: it is correct only while the
     * currency is USD, so any other currency is suffixed with its code rather
     * than being silently mislabelled as dollars.
     */
    public static function formatted(): string
    {
        $amount = number_format((float) config('subscription.price'), 2);

        if (str_ends_with($amount, '.00')) {
            $amount = substr($amount, 0, -3);
        }

        return self::currency() === 'USD'
            ? '$'.$amount
            : $amount.' '.self::currency();
    }

    /**
     * "$2.25" — the yearly price divided across the months it covers.
     *
     * A smaller number reads as a smaller commitment, which is why the offer
     * quotes it. That also makes it the most misleading figure on the site if it
     * ever appears alone: nobody is charged $2.25, and there is no month they
     * could cancel after. Every caller must put the real charge beside it, and
     * StaticOfferTest asserts the offer does.
     *
     * Rounded up rather than down, and to the cent. Rounding down would quote a
     * price whose twelve instalments come to less than the amount actually taken.
     *
     * Returns null for any interval that is not yearly. A monthly-billed plan has
     * no monthly equivalent to derive — the price *is* the monthly figure — and a
     * silent division by a different period is how a plan change ends up
     * misquoted on the homepage.
     */
    public static function monthlyEquivalent(): ?string
    {
        if (BillingInterval::configured() !== BillingInterval::Year) {
            return null;
        }

        $amount = number_format(ceil((float) config('subscription.price') / 12 * 100) / 100, 2);

        return self::currency() === 'USD'
            ? '$'.$amount
            : $amount.' '.self::currency();
    }

    /**
     * "$27/year", for the buttons and email actions that need the period in the
     * same breath as the amount.
     */
    public static function perInterval(): string
    {
        return self::formatted().'/'.BillingInterval::configured()->value;
    }
}
