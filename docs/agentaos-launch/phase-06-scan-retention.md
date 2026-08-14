# Phase 6 — Enforce the scan retention window

**Goal:** the 24-month scan retention period stated in the privacy policy is
enforced by code, not by intention.

**Blocks submission:** no — but [Phase 4](./phase-04-privacy-policy.md) publishes
the number, and an unenforced retention period is exactly the kind of untrue
claim this whole plan exists to remove.

---

## Why

Phase 4 §7 tells visitors that scan records are kept for 24 months and then
deleted automatically. Nothing deletes them today. `qr_code_scans` grows forever.

If Phase 6 does not ship, Phase 4 must not publish the number — we would have
replaced the false Google Analytics claim with a false retention claim, which is
no improvement. Ship them together.

Two independent reasons this is worth doing regardless of the policy:

- **Data minimisation.** Scan rows describe third parties (see
  [Phase 5](./phase-05-remove-scanner-ip.md)). Keeping them indefinitely is hard
  to justify under GDPR when the analytics value decays after a year or two.
- **The table is the fastest-growing thing we own.** One row per scan, forever,
  with no bound. A popular code writes rows at whatever rate the poster gets
  looked at. `throttle:60,1` caps abuse but not legitimate volume.

## New

### `config/site.php`

```php
/*
|--------------------------------------------------------------------------
| Scan retention
|--------------------------------------------------------------------------
|
| How many months of scan records are kept before scans:prune deletes them.
| This number is published in the Privacy Policy, so it is not a tuning knob:
| lowering it silently is fine, raising it is a change to a statement we have
| made to the people in those rows.
|
| Long enough for a subscriber to compare a campaign against the same month
| last year; short enough that we are not holding third-party scan data
| indefinitely.
|
*/

'scan_retention_months' => (int) env('SITE_SCAN_RETENTION_MONTHS', 24),
```

Lives in `config/site.php` rather than `config/subscription.php` because it is a
privacy commitment cited by a legal page, not a billing parameter.

### `app/Console/Commands/PruneScans.php`

```
php artisan make:command PruneScans --no-interaction
```

Signature `scans:prune`, following `billing:sync` and `billing:notify`.

Behaviour:

- Delete from `qr_code_scans` where `scanned_at` is older than
  `now()->subMonths(config('site.scan_retention_months'))`
- **Chunk the delete.** A single `DELETE` across a large table locks it and
  blocks the redirect path, which is the one query that must never be slow —
  every scan of every dynamic code goes through it. Delete in batches with a
  `limit`, looping until no rows remain.
- Report the count deleted to stdout so a scheduled run leaves a trace
- Support `--dry-run` to print what would go without touching anything. The
  first production run deletes real rows irreversibly; being able to look first
  is worth the ten lines.

Do **not** decrement `qr_codes.scan_count` when pruning. That counter is the
lifetime total the subscriber sees, it is incremented independently
(`QrCodeRedirectController.php:79`), and pruning old detail rows should not make
a customer's headline number fall. Note this in the command's docblock — it looks
like an inconsistency until you know it is deliberate.

### Schedule

In `routes/console.php`, beside the existing entries:

```php
/*
 * The Privacy Policy states that scan records are kept for a fixed window and
 * then deleted. This is the only thing that makes that true.
 */
Schedule::command('scans:prune')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping();
```

Weekly, not daily — the window is 24 months, so a few days of imprecision at the
boundary is immaterial, and there is no reason to run a bulk delete every night.
04:00 keeps it clear of `billing:sync` at 03:00.

> ⚠️ The existing comment at `routes/console.php:11-19` warns that all of this
> depends on a `schedule:run` cron entry existing on the server. Confirm it is
> configured on Laravel Cloud, or this phase is a no-op and Phase 4's claim is
> false in production while passing every test locally.

## Tests

New `tests/Feature/ScanPruningTest.php`:

- `test_it_deletes_scans_older_than_the_retention_window` — a scan at
  `now()->subMonths(25)` goes
- `test_it_keeps_scans_inside_the_retention_window` — one at `subMonths(23)` stays
- `test_it_follows_the_configured_window` — set
  `site.scan_retention_months` to 1, assert a 2-month-old scan goes and a
  2-week-old one stays. The point is that the number is not hardcoded, since the
  privacy policy renders from the same config value.
- `test_it_does_not_change_the_lifetime_scan_count` — prune, then assert
  `qr_codes.scan_count` is untouched
- `test_the_dry_run_deletes_nothing`
- `test_it_prunes_blocked_scans_too` — blocked rows
  (`QrCodeRedirectController.php:28`) are scan records like any other and are
  covered by the same published retention period

## Done when

- [ ] `php artisan scans:prune --dry-run` reports a count and deletes nothing
- [ ] `php artisan scans:prune` deletes only rows outside the window
- [ ] The window comes from config, and Phase 4's policy renders the same value
- [ ] `scans:prune` is scheduled and `php artisan schedule:list` shows it
- [ ] The cron entry is confirmed present on Laravel Cloud
- [ ] `php artisan test --compact --filter=ScanPruningTest` green
