# Phase 5 — Stop storing scanner IP addresses

**Goal:** we no longer retain, or expose to our customers, the IP address of
people who scan a QR code.

**Blocks submission:** no. Done anyway — it was the largest genuine privacy
exposure in the application.

**Status:** ✅ implemented and tested. Suite 256 → 262 tests, 795 → 807 assertions.
Privacy Policy §3 updated in the same commit, so Phase 4's scan-data follow-up is
discharged.

---

## Why

`QrCodeRedirectController.php:87` stores a full IP address for every scan:

```php
$qrCode->scans()->create([
    'scanned_at'  => now(),
    'blocked'     => $blocked,
    'ip_address'  => request()->ip(),   // ← this line
    'user_agent'  => request()->userAgent(),
    ...
]);
```

The column is `string('ip_address', 45)` (`2024_11_25_115039_create_qr_code_scans_table.php:18`)
— 45 characters, so full IPv6, unmasked. There is no retention limit, so these
accumulate indefinitely.

And `ScansRelationManager.php:45-48` shows them to the code's owner:

```php
Tables\Columns\TextColumn::make('ip_address')
    ->searchable()
    ->toggleable()
    ->toggledHiddenByDefault(),
```

`toggledHiddenByDefault()` hides the column until the customer clicks to show it.
It is one click, and the column is `searchable()` — so a subscriber can search
their scan log by IP address.

### Why this is the sharp one

An IP address is personal data under GDPR. The people in this table are not our
users — they scanned a poster or a menu. They have no account, never saw our
privacy policy, and have no practical way to know the record exists.

So we are collecting identifiable data about third parties, retaining it forever,
and disclosing it to a paying customer, with no disclosure and no retention
limit. Of everything in this plan, that is the item most likely to be a real
problem rather than a presentational one.

### What we lose

Nothing. Walk the actual uses:

| Use | Needs IP? |
|---|---|
| `scan_count` on the code | No — a counter |
| Country column | No — comes from Cloudflare's `CF-IPCountry` header (`QrCodeRedirectController.php:106-115`) |
| Device / OS / browser | No — parsed from the user agent |
| Scan charts and grouping | No — groups by country, device, browser, os (`ScansRelationManager.php:91-96`) |
| Blocked-scan counting | No — the `blocked` flag |

Nothing reads `ip_address` except the table column that displays it. Unique-visitor
counting would be the one plausible use, and it is not implemented. If we ever
want it, a daily-rotating salted hash gives us uniqueness without retaining
the address — that is a future feature, not a reason to keep this column.

Rate limiting still uses the IP: `routes/web.php:24-25` puts `throttle:60,1` on
the redirect route. That works on the live request and stores nothing.

## Migration

```
php artisan make:migration drop_ip_address_from_qr_code_scans_table --no-interaction
```

Drop `ip_address` from `qr_code_scans`.

**Drop `city` in the same migration.** `2024_11_25_115039_create_qr_code_scans_table.php:25`
declares it and nothing has ever written to it — `recordScan()` does not set it and
no view reads it. While the table is being altered anyway, an always-null column
claiming to hold a scanner's city is worth removing rather than leaving for someone
to start populating.

The `down()` method must restore the column with its original definition —
`string('ip_address', 45)->nullable()` — per the project rule that a modified
column keeps every previously defined attribute. The data is not recoverable on
rollback, which is the point.

> ⚠️ This deletes production data. That is the intent, and there is no reason to
> preserve it — but it is irreversible, so say so in the migration docblock.

> **Leave `sessions.ip_address` alone.** `0001_01_01_000000_create_users_table.php:33`
> is Laravel's own sessions table. That IP belongs to the account holder, is
> genuinely useful for session security, has a 2-week lifetime, and gets
> disclosed in Phase 4 §2b. Different thing entirely.

## Edit

| File | Change |
|---|---|
| `app/Http/Controllers/QrCodeRedirectController.php:87` | Delete the `ip_address` line |
| `app/Filament/Resources/QrCodeResource/RelationManagers/ScansRelationManager.php:45-48` | Delete the column |

The docblock on `recordScan()` (lines 65-74) already explains why nothing in
that method may reach off the server. Extend it with one line on why no IP is
kept, so the next person does not helpfully add it back.

`app/Models/QrCodeScan.php` needs no change — it has no `$fillable` and does not
cast the field.

## Tests

New `tests/Feature/ScanPrivacyTest.php` — or extend the existing
`tests/Feature/ScanPageTest.php`:

- `test_a_scan_records_no_ip_address` — hit `/q/{shortUrl}` from a known IP via
  `withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])`, then assert the created
  scan row holds that value nowhere. Assert against the row's full attribute
  array, not just a named column, so the test still fails if someone reintroduces
  the address under a different name.
- `test_the_scans_table_has_no_ip_address_column` — `Schema::hasColumn('qr_code_scans', 'ip_address')`
  is false. Cheap, and it fails loudly if the migration is ever reverted.
- `test_a_scan_still_records_country_device_and_browser` — the regression guard
  in the other direction. Removing the IP must not quietly break the analytics
  the subscription is sold on.
- `test_a_blocked_scan_records_no_ip_address` — the blocked path calls
  `recordScan()` too (`QrCodeRedirectController.php:28`) and is easy to forget.

Then run the existing scan suites, which touch this controller:

```
php artisan test --compact --filter=ScanPageTest
php artisan test --compact --filter=ScanGateTest
```

## Found during implementation

**The IP-absence test is mutation-verified.** `test_a_resolved_scan_stores_the_scanner_ip_nowhere`
asserts against `json_encode($scan->getAttributes())` rather than a named column,
so it catches the address reappearing under *any* column name. Verified by
temporarily changing `recordScan()` to write `request()->ip()` into `referer`:
two tests failed, and passed again on restore. The assertion is not vacuous.

**The migration round-trips.** Verified on an isolated sqlite file — never against
the dev MySQL database — that `up()` drops both columns, `down()` restores both
with their original definitions, and the migration re-applies cleanly afterwards.

**Nothing read the column but the display.** Confirmed before deleting: the country
comes from `CF-IPCountry`, device/OS/browser from the user agent, `scan_count` is a
counter, blocked-scan counting uses the `blocked` flag, and the route's
`throttle:60,1` reads the address live without storing it. The only consumer was
the Filament column that showed it to the customer.

**A note left in the controller.** `recordScan()`'s docblock now says explicitly
that no IP is recorded and why, and points at a daily-rotating salted hash as the
way to get unique-visitor counting later. The next person to want that number
should not rediscover the column as the obvious answer.

## Done when

- [ ] `grep -rn "ip_address" app/` returns nothing outside the sessions context
- [ ] The scans table has no `ip_address` column
- [ ] Country, device, OS and browser analytics still work end to end
- [ ] A subscriber cannot see or search an IP anywhere in the panel
- [ ] `php artisan test --compact --filter=Scan` green
