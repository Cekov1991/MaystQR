<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trial
    |--------------------------------------------------------------------------
    |
    | Days of free access granted at registration. The trial is entirely ours:
    | AgentaOS has no trial mechanism and never learns that one is running.
    |
    */

    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Grace
    |--------------------------------------------------------------------------
    |
    | Days of entitlement granted beyond the end of a paid period. This absorbs
    | the card processor's retry window so that a single declined renewal does
    | not immediately darken QR codes that are already printed.
    |
    */

    'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Price
    |--------------------------------------------------------------------------
    |
    | AgentaOS is Merchant of Record and prices are tax inclusive: the buyer is
    | charged this amount in total and destination VAT is carved out of it.
    |
    */

    'price' => (float) env('SUBSCRIPTION_PRICE', 27),

    'currency' => env('SUBSCRIPTION_CURRENCY', 'USD'),

    'billing_interval' => 'year',

    /*
    |--------------------------------------------------------------------------
    | Quotas
    |--------------------------------------------------------------------------
    |
    | Default ceilings on how many QR codes of each type a user may create.
    | Overridable per account via users.dynamic_qr_limit / users.static_qr_limit.
    | Quotas gate creation only — they never stop an existing code resolving.
    |
    */

    'quotas' => [
        'dynamic' => (int) env('SUBSCRIPTION_DYNAMIC_QR_LIMIT', 5),
        'static' => (int) env('SUBSCRIPTION_STATIC_QR_LIMIT', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    |
    | Where to send operational billing alerts: a payment nobody could be
    | attributed to, a webhook rejected for a bad signature, a reconciliation
    | run that could not finish. These are the failures a customer discovers
    | before we do. Leave the address blank to log without emailing.
    |
    | Repeats of the same kind are suppressed for the throttle window, so a
    | rotated secret or an API outage cannot bury the inbox.
    |
    */

    'alert_email' => env('BILLING_ALERT_EMAIL'),

    'alert_throttle_minutes' => (int) env('BILLING_ALERT_THROTTLE_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Missing subscription cap
    |--------------------------------------------------------------------------
    |
    | How many live subscriptions may disappear from an AgentaOS listing in a
    | single billing:sync run before the run refuses to close any of them and
    | raises an alert instead. A listing that drops many at once is far more
    | likely to be an API fault than genuine mass cancellation.
    |
    | Zero disables the cap, which lets one bad reply close every subscription.
    |
    */

    'max_missing_per_sync' => (int) env('SUBSCRIPTION_MAX_MISSING_PER_SYNC', 10),

];
