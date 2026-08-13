# Subscription MVP — Implementation Plan

Yearly $27 subscription with a 7-day account-level trial, billed through AgentaOS.

**Status:** phases 0–5 implemented and tested; not yet deployed.
**Related:** [CONTEXT.md](../CONTEXT.md) · [ADR-0001](./adr/0001-account-level-entitlement-replaces-per-code-expiry.md) · [ADR-0002](./adr/0002-entitlement-is-a-local-date-reconciled-from-agentaos.md)

---

## The product in one paragraph

A User registers and gets 7 days during which everything works. Static QR Codes are free forever and unaffected by any of this — they encode their destination directly, so MaystQR is never in the scan path. Dynamic QR Codes resolve through `/q/{shortUrl}`, and they only resolve while the Owner holds an Entitlement. When the trial ends without payment, the account goes Lapsed: the Owner keeps their library, their analytics, and their ability to make static codes, but their dynamic codes stop resolving and a Scanner gets a neutral "not active" page. $27/year through AgentaOS restores them.

## Decisions

| Area | Decision |
|---|---|
| Trial scope | Account-level. `users.trial_ends_at`, 7 days from registration. Per-code expiry retired. |
| Lapsed access | Static free (to 50), library and analytics readable, existing dynamic codes editable but not resolving, no new dynamic codes. |
| Existing users | Migration backfills a fresh 7 days from deploy date. Nobody goes dark on deploy day. |
| Payment tracking | Webhook grants immediately; daily poll of `GET /gateway/subscriptions` reconciles renewals, cancellations and `past_due`. |
| Scanner experience | Neutral branded "not active" page. No owner identity, no destination, no analytics. Owner-aware: shows a reactivate CTA if the logged-in viewer owns the code. |
| Grace | `entitled_until = currentPeriodEnd + 7 days`. Status never revokes; only the clock does. |
| Quotas | 5 dynamic / 50 static from config, overridable per user. Gates creation only, never resolution. |
| Dead code | PayPal + package + old Subscription models deleted before new work starts. |
| Emails | Three: trial ending (T-2), trial ended, renewal payment failed. |

## Pricing note

AgentaOS is Merchant of Record and **tax is inclusive** — the buyer pays $27 total and destination VAT is carved out of it, not added on top. A German buyer at 19% VAT leaves roughly $22.69 gross before AgentaOS's own fee. Price the product knowing that.

---

## Phase 0 — Demolition

**Goal:** one billing concept in the codebase, and the `Subscription` class name freed up.

### Delete

| Kind | Files |
|---|---|
| Services | `app/Services/PayPalService.php` |
| Controllers | `app/Http/Controllers/PayPalController.php`, `QrCodePackageController.php`, `QrCodeExpiredController.php` |
| Commands | `app/Console/Commands/TestPayPal.php` |
| Models | `app/Models/Subscription.php`, `SubscriptionPlan.php`, `PaymentMethod.php`, `QrCodePackage.php`, `QrCodePackagePurchase.php` |
| Views | `resources/views/qr-expired.blade.php`, `qr-package-purchase.blade.php`, `refund-policy.blade.php`, `filament/resources/payment-method-resource/pages/manage-payment-method.blade.php` |

`QrCodePackageController.php:62` contains a live `dd($e)` in a catch block — it leaves with the file.

### Edit

- `routes/web.php` — remove the PayPal group (48–52), the commented package block (54–67), the commented refund-policy route (73), and the `qr.expired` / `qr.extend` routes (30–35). Fix `/landing-page` (21–24), which queries `QrCodePackage::active()`.
- `resources/views/welcome.blade.php` — remove its `$packages` usage.
- `config/services.php` — drop the `paypal` block.
- `config/app.php` — drop `free_dynamic_qr_codes`. Keep `qr_code_trial_days` for now; it moves to `config/subscription.php` in phase 1.
- `app/Models/QrCode.php` — remove `isExpired()`, `isActive()`, `isInTrial()`, `getTimeUntilExpiry()`, `extendValidity()`, `canBeScanned()`, `scopeExpired()`, `packagePurchases()`, and the `expires_at` stamping inside `boot()`. Keep `scopeActive()` only if still used elsewhere; otherwise remove.
- `app/Models/User.php` — remove `paymentMethods()`, `getPaymentMethod()`, `hasPaymentMethod()`, `qrCodePackagePurchases()`, `getExpiredQrCodes()`.
- `app/Http/Controllers/QrCodeRedirectController.php` — remove the expired branch and the now-dead `expired()` method.

### Migration

Drop `payment_methods`, `qr_code_packages`, `qr_code_package_purchases`.

`qr_codes.expires_at` **stays** as an inert column — dropping it is a separate decision and the column is harmless. Nothing may read it as a gate again (ADR-0001).

> ⚠️ **Verify `qr_code_package_purchases` is empty in production before this migration runs.** The purchase routes were commented out so it should be, but confirm rather than assume.

### Done when

- `php artisan test --compact` is green.
- `php artisan route:list` resolves with no missing-controller errors.
- `/`, `/landing-page`, `/q/{shortUrl}` and the two Filament panels all load.
- Dynamic codes resolve unconditionally — **intended at this stage**; phase 2 installs the real gate.

---

## Phase 1 — Entitlement core

**Goal:** the app knows every account's billing state. No payment, no gates yet.

### Schema — `users`

| Column | Type | Notes |
|---|---|---|
| `trial_ends_at` | `timestamp` nullable | Immutable record of the trial window |
| `entitled_until` | `timestamp` nullable | **The gate.** Denormalised so `/q/{shortUrl}` needs no join |
| `dynamic_qr_limit` | `unsignedTinyInteger` nullable | Per-user override; null → config |
| `static_qr_limit` | `unsignedSmallInteger` nullable | Per-user override; null → config |

Backfill: every existing row gets `trial_ends_at = entitled_until = now()->addDays(7)`.

`entitled_until` always holds `max(trial_ends_at, subscription.current_period_end + grace)`. It is written by the trial stamp, the payment webhook, and the daily sync — and **never shortened by a failed API call** (ADR-0002).

### Code

- `app/Observers/UserObserver.php` — on `creating`, stamp `trial_ends_at` and `entitled_until` to `now() + config('subscription.trial_days')`.
- `app/Models/User.php` — `isEntitled()`, `isTrialing()`, `isLapsed()`, `accountState()`, `trialDaysRemaining()`.
- `app/Services/QrCodeQuota.php` — `limitFor(string $type)`, `usedFor(string $type)`, `remainingFor(string $type)`, `canCreate(string $type)`. Reads `users.{type}_qr_limit ?? config('subscription.quotas.{type}')`.
- `config/subscription.php` — trial days (7), grace days (7), price (27), currency (USD), quotas (dynamic 5, static 50), and the AgentaOS keys added in phase 3.

### Tests

- A new user gets a trial 7 days out; `isTrialing()` true, `isLapsed()` false.
- Travelling 8 days forward flips to Lapsed.
- The backfill migration gives existing users a future `trial_ends_at`, regardless of `created_at`.
- Quota counts by type, respects a per-user override, ignores the other type.

---

## Phase 2 — Gates and the lapsed experience

**Goal:** entitlement actually decides what happens, everywhere it should.

### Scan path

`QrCodeRedirectController::redirect()` — for `type === 'dynamic'`, if the Owner is not entitled, render `resources/views/qr/inactive.blade.php` instead of resolving.

The page shows: a neutral "This QR code isn't active right now", a soft "Made with MaystQR" line, and **nothing else** — no owner name or email, no destination URL, no scan counts. If `auth()->id() === $qrCode->user_id`, it instead shows "Your subscription has lapsed — reactivate to bring your N dynamic codes back online" with a link to billing.

Static codes never reach this controller, which is what makes "static stays free" free to implement.

### Blocked-scan logging (small, high value)

Add `blocked` boolean to `qr_code_scans` and log blocked scans without incrementing `scan_count`. This makes "you missed 340 scans while inactive" available as the reactivation argument on the billing page and in the trial-ended email. One column, disproportionate payoff.

### Panel

- `QrCodeResource::canCreate()` returns false for dynamic when lapsed or at quota, with a guard repeated server-side on the create page (Filament's `canCreate` is UI-level; the guard is the enforcement).
- Dashboard banner widget: trial countdown while Trialing, reactivate CTA while Lapsed.
- The public `/free` panel is unaffected — it stores to session and pushes to registration, where quotas apply on the real row.

### Tests

- Entitled owner → normal redirect and a logged scan.
- Lapsed owner → inactive view, `scan_count` unchanged, blocked scan logged.
- Lapsed owner viewing their own code while logged in → reactivate CTA present.
- Stranger on a lapsed code → no owner email, no destination URL in the response body.
- Lapsed user cannot create a dynamic code; can still create a static one.
- User at 5 dynamic codes cannot create a 6th; a user already over the limit keeps all of theirs resolving.

---

## Phase 3 — AgentaOS integration

**Goal:** money in, state tracked.

### Config — `config/services.php`

```php
'agentaos' => [
    'key'             => env('AGENTAOS_API_KEY'),
    'webhook_secret'  => env('AGENTAOS_WEBHOOK_SECRET'),
    'payment_link_id' => env('AGENTAOS_PAYMENT_LINK_ID'),
    'base_url'        => env('AGENTAOS_BASE_URL', 'https://api.agentaos.ai/api/v1'),
],
```

### Schema — `subscriptions` (new table)

| Column | Notes |
|---|---|
| `user_id` | FK, indexed |
| `checkout_session_id` | nullable unique — known at checkout creation |
| `agentaos_subscription_id` | nullable unique — resolved after first payment |
| `status` | mirrors AgentaOS: `incomplete`, `trialing`, `active`, `past_due`, `canceled`, `unpaid`, `paused` |
| `current_period_end` | nullable timestamp |
| `unit_amount_minor` | integer — `2700` for $27 |
| `currency` | string |
| `cancel_at_period_end` | boolean |

### Client — `app/Services/AgentaOS/AgentaOsClient.php`

`Http::withHeaders(['x-api-key' => …])->baseUrl(…)->timeout(10)->retry(2, 200)`.

| Method | Call |
|---|---|
| `createCheckout(User $user)` | `POST /gateway/sessions` — `linkId`, `buyerEmail`, `metadata: {user_id}`, `successUrl`, `cancelUrl`, `idempotencyKey` |
| `listSubscriptions(int $limit, int $offset)` | `GET /gateway/subscriptions` |
| `cancelSubscription(string $id, bool $atPeriodEnd = true)` | `POST /gateway/subscriptions/{id}/cancel` |

### Flow

1. **Subscribe** — `SubscriptionController::checkout()` creates the AgentaOS checkout, stores a `subscriptions` row with `checkout_session_id` and status `incomplete`, redirects to `checkoutUrl`.
2. **Webhook** — `POST /webhooks/agentaos`, CSRF-excluded via `bootstrap/app.php` (`$middleware->validateCsrfTokens(except: ['webhooks/*'])`). Verify HMAC-SHA256 over the **raw** request body, `hash_equals`, 300s tolerance. Verify → queue → return 200. Never branch on the payload before verification passes.
3. **Grant job** — idempotent on `data.session_id`. Reads `metadata.user_id`, marks the row active, sets `entitled_until = now() + 1 year + grace` provisionally.
4. **Resolve job** — calls `listSubscriptions()` to fill `current_period_end` and `unit_amount_minor`, matching on the `agentaos_subscription_id` the grant job took from `metadata.subscriptionId`, or falling back to `customerEmail` for payments whose metadata carried no id. Retries with backoff; alerts if it can't match.
5. **Daily `billing:sync`** — pages through subscriptions, matches on `agentaos_subscription_id` (falling back to email for unresolved rows), updates `status` and `current_period_end`, recomputes `entitled_until = max(trial_ends_at, current_period_end + grace)`. **May extend or no-op. May never shorten.**

### Payment link

An artisan command (`agentaos:create-payment-link`) creates it — `type: subscription`, `billingInterval: year`, `amount: 27`, `currency: USD` — and prints the UUID for `.env`. A command rather than dashboard clicks, so test and live are reproducible.

### Scheduler — `routes/console.php`

```php
Schedule::command('billing:sync')->dailyAt('03:00')->withoutOverlapping();
```

### Tests

- `Http::fake()` for every client method, asserting request shape.
- Webhook: valid signature accepted; bad signature 400; signature older than 300s rejected; body tampering rejected.
- Duplicate webhook delivery grants once (idempotency on `session_id`).
- Sync extends `entitled_until` on a renewal, and leaves it untouched when the API errors or the subscription is missing.
- `past_due` does not shorten `entitled_until`.

---

## Phase 4 — Billing UI and emails

**Goal:** the user can pay, cancel, and find out what's happening without logging in.

### Panel

A Filament billing page in the admin panel showing account state, price, period end where applicable, a subscribe button, and cancel-at-period-end. Cancelling calls `cancelSubscription($id, atPeriodEnd: true)` — the user keeps what they paid for and lapses naturally when the clock runs out.

### Notifications (queued, existing Brevo SMTP)

| Notification | Trigger |
|---|---|
| `TrialEndingSoon` | `trial_ends_at` is 2 days out |
| `TrialEnded` | `entitled_until` has passed and no subscription covers today |
| `RenewalPaymentFailed` | status is `past_due` inside the grace window |

Fired from the daily `billing:sync`. Each gets a `*_notified_at` column on `users` so nothing sends twice.

The trial-ended email is the natural home for the blocked-scan count from phase 2.

### Tests

`Notification::fake()` — each fires once at the right boundary, and does not re-fire on the next day's run.

---

## Phase 5 — Go-live

Three items have **lead time and must start early**:

1. **AgentaOS business verification** — required before a `sk_live_` key is issued. This is the only item that depends on no code at all. **Start it during phase 0.**
2. **Cron running `schedule:run`** — there are currently no scheduled tasks in this project, so this has never been needed. Without it, phases 3 and 4 silently do nothing.
3. **A queue worker** — `QUEUE_CONNECTION=database` means webhooks and emails sit in the `jobs` table forever unless something runs `queue:work`.

### Checklist

- [ ] Business verification approved, `sk_live_` key issued
- [ ] `* * * * * php artisan schedule:run` installed
- [ ] `queue:work` running under supervisor
- [ ] `.env.example` documents `AGENTAOS_API_KEY`, `AGENTAOS_WEBHOOK_SECRET`, `AGENTAOS_PAYMENT_LINK_ID`, `AGENTAOS_BASE_URL`; `PAYPAL_*` removed
- [ ] Webhook URL registered at app.agentaos.ai → Developer → Webhooks, signing secret stored
- [ ] Full flow exercised on `sk_test_` — subscribe, webhook, grant, sync, cancel
- [ ] Live payment link created and its UUID in production env
- [ ] `qr_code_package_purchases` confirmed empty before the phase 0 drop
- [x] Terms and conditions updated; refund policy page written and its route uncommented
- [ ] `php artisan filament:optimize` in the deploy script
- [ ] Someone who did not write it reads the refund policy

### Runbook

**1. Before deploying, verify the destructive migration is safe**

```sql
SELECT COUNT(*) FROM qr_code_package_purchases;  -- must be 0
SELECT COUNT(*) FROM payment_methods;            -- must be 0
```

`..._drop_paypal_and_qr_package_tables` drops both.

**2. Environment** — set `AGENTAOS_API_KEY`, `AGENTAOS_WEBHOOK_SECRET`, `AGENTAOS_PAYMENT_LINK_ID`. Point `CACHE_STORE` at `database` or `redis`; `array` gives the scheduler no real lock.

**3. Register the webhook** at app.agentaos.ai → Developer → Webhooks → `https://<domain>/webhooks/agentaos`, and store the `whsec_...` secret. Until it is set, **every webhook is rejected** and no payment is credited — verification fails closed by design.

**4. Create the payment link** with `php artisan agentaos:create-payment-link`; it prints the UUID. Once per environment — test and live keys create separate links.

**5. Cron and queue, both new to this project**

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

```bash
php artisan queue:work --tries=3     # under supervisor or systemd
```

No cron means `billing:sync` and `billing:notify` never run. No worker means webhooks sit in the `jobs` table and no payment is ever credited. Neither failure is loud — check both after deploying.

**6. Exercise on `sk_test_` first:** subscribe → pay → webhook lands and `entitled_until` jumps ~1 year + 7 days → `billing:sync` fills `agentaos_subscription_id` → cancel and confirm access survives to `current_period_end`.

### Migrations added

| Migration | Effect |
|---|---|
| `..._drop_paypal_and_qr_package_tables` | **Destructive.** Drops `qr_code_package_purchases`, `qr_code_packages`, `payment_methods`. |
| `..._add_entitlement_columns_to_users_table` | Adds `trial_ends_at`, `entitled_until`, `dynamic_qr_limit`, `static_qr_limit`; backfills every existing user with a fresh trial from deploy time. |
| `..._add_blocked_to_qr_code_scans_table` | Adds `blocked`. |
| `..._create_subscriptions_table` | New table. |
| `..._add_notification_timestamps_for_billing` | Adds the three send-once guards. |

---

## Deviations from the original plan

Recorded so the next reader is not confused by the difference between this document's phases and the code.

1. **Phase 0 was larger than scoped.** A grep before deleting turned up references the plan had missed: both panels' `QrCodeResource`, `ViewQrCode`, `QrCodePackageSeeder`/`DatabaseSeeder`, and three blade files (`layouts/qr`, `terms-and-conditions`, `welcome`). All were cleaned in the same phase.
2. **`phpunit.xml` had its SQLite override commented out**, so `RefreshDatabase` was running `migrate:fresh` against the development MySQL database and wiping it on every test run. Now pinned to `sqlite` / `:memory:`.
3. **Emails run in their own command, `billing:notify`, not inside `billing:sync`.** The trial is entirely ours, so trial warnings must keep sending when AgentaOS is unreachable or not yet configured. `billing:sync` aborts without an API key; `billing:notify` never needs one.
4. **`TrialEnded` covers both lapse routes.** It fires whenever entitlement runs out — an unconverted trial *or* a cancelled subscription reaching its period end — and adapts its wording. This avoids a fourth email for churned subscribers, who would otherwise have been told nothing.
5. **Blocked-scan logging was built** (flagged optional in the plan). `qr_code_scans.blocked` records scans that hit an unentitled code without inflating `scan_count`, and the count appears in the owner-facing inactive page and the `TrialEnded` email.
6. **Re-arm columns.** `access_ended_notified_at` is cleared whenever entitlement is restored, and `past_due_notified_at` whenever a subscription recovers, so a second lapse or a second failed renewal is not swallowed by a stale timestamp.

## Known issues, not fixed

- **`Model::unguard()` is called globally** in `AppServiceProvider::boot()`, which disables mass-assignment protection application-wide. `User::$fillable` deliberately excludes `entitled_until`, `trial_ends_at` and the quota overrides, but that exclusion is inert while unguarding is on. No current code path passes request input into a `User::create`/`fill`, so this is latent rather than live — but a future `User::update($request->validated())` with a stray field could hand out free years. Removing the global unguard means adding `$fillable` to the models that rely on it (`QrCodeScan`, `Subscription`) and re-testing every Filament resource.
- **`paypal/paypal-checkout-sdk` is still in `composer.json`.** Nothing references it after phase 0. Left in place because changing dependencies needs approval.
- **`CACHE_STORE=array`** in the local environment means `withoutOverlapping()` on the scheduled commands holds no real lock across processes. Harmless on a single-server cron, but worth pointing at `database` or `redis` in production.
- **The `/landing-page` marketing page has no pricing section.** The package-based one was removed in phase 0 and not replaced; the live homepage is `home.blade.php`, and `/landing-page` is parked pending a redesign.

## Open questions for AgentaOS

Neither blocks the build.

1. **Does an annual renewal re-fire `checkout.session.completed`?** Undocumented. If yes, the daily sync is a safety net; if no, it is the primary mechanism. Either way the design holds — but confirm well before the first renewals land.
2. **Can the buyer edit the `buyerEmail` we pass at the hosted checkout?** Email is the only join key between an AgentaOS Subscription and a `users` row, so a buyer paying under a different address is the one thing that breaks attribution.

---

## Appendix — AgentaOS reference

Base URL `https://api.agentaos.ai/api/v1` · auth header `x-api-key: sk_test_…` / `sk_live_…` · rate limit 60 requests per 60 seconds per IP · list endpoints take `limit` (default 20, max 100) and `offset`, returning `{ items, total, hasMore }`.

**Money:** a plain `amount` is decimal currency units (`27` = $27). Fields ending in `Minor` are integer minor units (`2700` = $27). Webhook `amount` is a **string** (`"27.00"`).

**Endpoints used**

```
POST /gateway/payment-links              once, via artisan command
POST /gateway/sessions                   per user, per subscribe attempt
GET  /gateway/subscriptions              daily sync + id resolution
POST /gateway/subscriptions/{id}/cancel  { "atPeriodEnd": true }
```

**Subscription object** — fully camelCase, and note what is *absent*: no `metadata`, no `session_id`, no `user_id`.

```
id · customerEmail · customerName · planName · billingInterval
status · unitAmountMinor · currency · currentPeriodEnd · stripeSubscriptionId
```

**Webhook events** — only three exist, and none of them concern subscription lifecycle:

```
checkout.session.completed · send.completed · send.failed
```

`checkout.session.completed` payload carries `link_id`, `session_id`, `amount` (string), `currency`, `rail`, `vendor_reference`, `payer_type`, `testnet`, and `metadata` — our `user_id` rides in `metadata`.

AgentaOS also *adds* to the metadata it returns, undocumented: a `payer` object and, on a subscription payment, `subscriptionId` — the id of the Subscription the payment created. Treated as a windfall rather than a contract; see ADR 0002.

**Signature** — header `X-AgentaOS-Signature: t=<unix>,v1=<hex>`, where `v1` is HMAC-SHA256 of `"{t}.{raw_body}"` keyed with the signing secret. Reject anything older than 300s. Compare with `hash_equals`, never `===`. Delivery is 3 attempts with exponential backoff, 10s response timeout each.
