<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
 * AgentaOS sends no subscription lifecycle webhooks, so renewals,
 * cancellations and failed cards are only visible by polling.
 *
 * This requires a cron entry on the server:
 *   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 *
 * Without it, subscriptions are never reconciled and lifecycle emails never
 * send. Entitlement still works — it just stops being kept up to date.
 */
Schedule::command('billing:sync')
    ->dailyAt('03:00')
    ->withoutOverlapping();

/*
 * Kept separate from billing:sync on purpose: the trial is ours alone, so
 * trial warnings must still go out when AgentaOS is unreachable.
 */
Schedule::command('billing:notify')
    ->dailyAt('09:00')
    ->withoutOverlapping();
