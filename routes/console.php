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

/*
 * Section 7 of the Privacy Policy tells people who scanned a QR code how long we
 * keep the record. This is the only thing that makes that true — without it the
 * page states a retention period that nothing enforces.
 *
 * Weekly rather than daily: the window is two years, so a few days of slack at
 * the boundary is immaterial, and there is no reason to run a bulk delete every
 * night. 04:00 keeps it clear of billing:sync at 03:00.
 */
Schedule::command('scans:prune')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping();

/*
 * The public create form uploads a centre logo before the QR code exists, so a
 * guest who abandons the registration leaves the file behind with nothing
 * pointing at it. This is the only thing that collects those.
 *
 * Daily rather than weekly: the files are user-supplied images on the billed
 * disk, and the grace period already gives a registration in progress two days
 * of protection. 04:30 keeps it clear of scans:prune on Mondays.
 */
Schedule::command('logos:prune')
    ->dailyAt('04:30')
    ->withoutOverlapping();
