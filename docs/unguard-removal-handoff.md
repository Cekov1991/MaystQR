# Handoff — remove `Model::unguard()` and restore mass-assignment guarding

**Status:** not started. Investigated and measured on 2026-08-27; nothing changed.
**Branch this was found on:** `worktree-event-spine-and-offer`. Independent of that work — do this on its own branch off `dev`.
**Related:** [event-spine-implementation-plan.md](./event-spine-implementation-plan.md) (where it surfaced) · `tests/Feature/StaticOfferTest.php` (pins the current state)

---

## The situation in one paragraph

`app/Providers/AppServiceProvider.php` calls `Model::unguard()` in `boot()`. That
sets a static flag making **every** model in the application fully
mass-assignable, so every `$fillable` array in `app/Models/` is decoration. The
User model's docblock still claims its entitlement columns "must never be
settable from request input", and that claim is currently false —
`trial_ends_at`, `entitled_until` and `signup_source` are all writable by
`fill()`. Nothing exploits this today, but the guard rail is off.

## Why it has not just been deleted

Because it is load-bearing, which was not obvious. Comment out the call and the
suite does not merely warn — it fails with `MassAssignmentException`:

```
84 failed, across 12 test classes

29  ScanPageTest                    4  ScanPrivacyTest
15  Subscription\ScanGateTest       4  Subscription\CheckoutTest
12  Subscription\BillingSyncTest    3  Subscription\BillingPageTest
 8  Subscription\WebhookTest        2  Subscription\BillingAlertTest
 4  Subscription\BillingNotificationsTest
 1  each: StaticOfferTest, FunnelReportTest, EventRecordingTest
```

**Root cause:** `Subscription` and `QrCodeScan` declare neither `$fillable` nor
`$guarded`, so with guarding on they accept nothing at all. Every other model
(`User`, `QrCode`, `SiteEvent`) already declares `$fillable`.

## It is a latent hazard, not a live hole

Audited on 2026-08-27. Every mass-assignment site is already safe by other
means, which is also why re-guarding should be behaviour-neutral:

| Site | Why it is safe today |
|---|---|
| `ProfileController::update` | `fill($request->validated())`, and `ProfileUpdateRequest` validates only `name` and `email` |
| `Filament\Pages\Auth\Register::handleRegistration` | `make($data)` from the form schema; `signup_source` set separately from an allowlisted enum |
| `QrCodeResource\Pages\CreateFromSession` | `$data` is a four-key literal written server-side by `Public\...\CreateQrCode`; `user_id` from `Auth::id()` |
| `SubscriptionController`, `GrantSubscriptionEntitlement`, `Billing`, `ResolveAgentaOsSubscription`, `TrackedEvent::record` | literal arrays of server-derived values |

So do not treat this as a security fix to rush. Treat it as removing a footgun
that currently happens to be pointed at the floor.

---

## The work

### 1. Add `$fillable` to `Subscription`

Table columns: `id`, `user_id`, `checkout_session_id`, `agentaos_subscription_id`,
`status`, `current_period_end`, `unit_amount_minor`, `currency`,
`cancel_at_period_end`, `past_due_notified_at`, timestamps.

The keys actually mass-assigned anywhere in `app/` are:

```php
protected $fillable = [
    'user_id',
    'checkout_session_id',
    'agentaos_subscription_id',
    'status',
    'current_period_end',
    'unit_amount_minor',
    'currency',
    'cancel_at_period_end',
];
```

`past_due_notified_at` is deliberately absent: `SendBillingNotifications` writes
it with `forceFill()`, which bypasses `$fillable` by design. Leave it out, and do
not "fix" that call site — notification bookkeeping is not request input.

### 2. Add `$fillable` to `QrCodeScan`

Table columns: `id`, `qr_code_id`, `scanned_at`, `blocked`, `user_agent`,
`referer`, `device`, `os`, `browser`, `country`, timestamps, `deleted_at`.

Created only via `$qrCode->scans()->create([...])` in
`QrCodeRedirectController`, which sets `qr_code_id` itself.

```php
protected $fillable = [
    'scanned_at',
    'blocked',
    'user_agent',
    'referer',
    'device',
    'os',
    'browser',
    'country',
];
```

**Do not add `qr_code_id`** unless a test forces it — the relationship supplies
it, and leaving it out means nothing can reassign a scan to a different code.

**Note the trap:** the table has `deleted_at` but the model does not use
`SoftDeletes`. That mismatch is pre-existing and documented in the `PruneScans`
docblock — the prune does real deletes. Do not add the trait to "fix" the column;
a retention window published in the Privacy Policy is only true while the deletes
are real. Removing the column is a separate job.

### 3. Remove the call

Delete `Model::unguard();` from `AppServiceProvider::boot()` and its now-unused
`Model` import if nothing else uses it.

### 4. Fix what still fails

Steps 1 and 2 should account for the great majority of the 84. Anything left is
a genuine find — a call site relying on unguarded assignment that nobody knew
about. Read each one rather than widening `$fillable` until it goes quiet:
**adding a column to `$fillable` to silence a test is how this footgun gets
rebuilt.** If a call site needs to write something that should not be
request-settable, use `forceFill()` there instead.

### 5. Update what currently documents the gap

Three places assert or describe the present state and must change together:

- `app/Models/User.php` — the `$fillable` docblock has a paragraph saying the list "currently protects nothing" because of `unguard()`. Delete that paragraph; the list will mean what it says again.
- `tests/Feature/StaticOfferTest.php::test_mass_assignment_guarding_is_currently_disabled_application_wide` — **this test is designed to fail when you do this work.** Its failure message says so. Replace it with the inverse: `signup_source` cannot be mass-assigned.
- `app/Filament/Pages/Auth/Register.php` — a docblock notes `$fillable` is not the protection because guarding is off. Reword: the protection becomes belt *and* braces.

---

## Verification

```bash
php artisan test --compact          # expect 0 failures
vendor/bin/pint --dirty --format agent
```

Then exercise the two models' real paths, because they are the ones with no
prior `$fillable` and the ones the tests cover least directly:

```bash
# a scan writes a row
php artisan test --compact tests/Feature/ScanPageTest.php tests/Feature/ScanPrivacyTest.php
# subscriptions still sync, grant and notify
php artisan test --compact tests/Feature/Subscription
```

A manual check worth doing before merging: scan a real dynamic code against a
local server and confirm a `qr_code_scans` row appears with its device, os,
browser and country populated. A silently-narrowed `$fillable` shows up as
*nulls in a row that still gets written*, which no test above would necessarily
catch.

## Rough size

Two model edits, one line deleted, and a pass over ~84 test failures that should
mostly evaporate with the first two. Half a day if nothing unexpected turns up in
step 4. The risk is not in the change but in its blast radius: it touches billing
and scan writing, which are the two paths where a silent failure costs money or
loses data a customer paid for.
