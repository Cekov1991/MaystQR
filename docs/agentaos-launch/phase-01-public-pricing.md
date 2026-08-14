# Phase 1 — Public pricing

**Goal:** a visitor who has never registered can find the price, the billing
period, the tax treatment and the trial terms in one click from the homepage.

**Blocks submission:** yes.
**Status:** ✅ implemented and tested. Suite 192 → 212 tests, 584 → 631 assertions.

---

## Why

The go-live form asks us to attest that *"my pricing is easily accessible and
clearly displayed to users before they purchase"*, and lists *"hidden pricing
until checkout"* in its **WRONG** column.

Today `$27` appears in exactly two places:

- `terms-and-conditions.blade.php:65`, inside clause 5 of a legal document
- `filament/pages/billing.blade.php`, which is **behind login**

The homepage Dynamic QR card (`home.blade.php:41-64`) says `PAID` and nothing
else — no amount, no period, no trial. A reviewer cannot learn what we charge
without creating an account, which is the exact failure the form describes.

## The duplication to fix on the way

The price is formatted by the same expression written out **four** times:

| Location | Form |
|---|---|
| `app/Filament/Pages/Billing.php:33` | `getFormattedPrice()` |
| `resources/views/terms-and-conditions.blade.php:65` | inline in Blade |
| `app/Notifications/TrialEndingSoon.php:54` | `private function price()` |
| `app/Notifications/TrialEnded.php:71` | `private function price()` |

Each is its own `rtrim(rtrim(number_format(...)))` chain, and the two
notifications additionally hardcode `'$'` and `'/year'` around it at
`TrialEndingSoon.php:50` and `TrialEnded.php:59` — the same shape as the bug
fixed in `18f75cb`, where config named the billing interval and the code
independently hardcoded a year.

Four copies is how the Terms, the emails and the subscribe button drift apart.
Phase 1 adds two more customer-facing prices, so the formatter moves to one
place first and all six read from it.

## New

### `app/Support/SubscriptionPrice.php`

Sits beside the existing `app/Support/BrandColor.php`.

```php
public static function formatted(): string;    // "$27"
public static function perInterval(): string;  // "$27/year"
public static function currency(): string;     // "USD"
```

`perInterval()` takes the period from `BillingInterval::configured()` rather
than a hardcoded `"year"`, so the price the customer reads and the interval sent
to AgentaOS cannot disagree — the same class of bug fixed in `18f75cb`.

Guard the currency symbol: `$` only while the currency is USD, otherwise suffix
the ISO code. A hardcoded `$` in front of a EUR price is a pricing misstatement.

### `resources/views/pricing.blade.php`

Extends `layouts.site`. Reuses `eq-card-grid`, `eq-card`, `eq-badge`,
`eq-check-list`, `eq-btn` — no new layout primitives. One small `.eq-price`
block is added to `public/css/site.css` for the figure, following the existing
`.eq-stat-figure` treatment.

Two cards, matching the homepage pair so the story is consistent:

| | Static | Dynamic |
|---|---|---|
| Price | Free, forever | **$27 / year** |
| Account | Not needed | Required |
| Trial | — | 7 days, no card required |
| Editable after printing | No | Yes |
| Scan analytics | No | Yes |
| Quota | 50 | 5 |

Copy that must appear verbatim, because each line answers a reviewer question:

> **$27 per year.** Billed once a year, renews automatically until you cancel.
> Tax included — AgentaOS is the merchant of record and handles VAT for your
> country, so the price above is the total you pay.
>
> Start with a **7-day free trial**. No payment details required to begin.
> Cancel any time from your account and you keep access until the end of the
> period you have paid for.

Then link the Refund Policy and the Terms directly beneath. A reviewer who can
see the price, the renewal behaviour, the tax treatment and the refund route on
one page has nothing left to fail us on.

### Route

```php
Route::view('pricing', 'pricing')->name('pricing');
```

Placed with the other `Route::view` legal pages in `routes/web.php:53-57`.

## Edit

| File | Change |
|---|---|
| `resources/views/home.blade.php:41-64` | Add `$27/year` and "7-day free trial, no card required" to the Dynamic QR card; link to `/pricing` |
| `resources/views/layouts/site.blade.php:38-44` | Add a **Pricing** link to the header nav |
| `resources/views/layouts/site.blade.php:53-58` | Add a **Pricing** link to the footer nav, before Terms |
| `resources/views/terms-and-conditions.blade.php:65` | Use `SubscriptionPrice::perInterval()` |
| `app/Filament/Pages/Billing.php:31-34` | Delegate `getFormattedPrice()` to `SubscriptionPrice::formatted()` |
| `app/Notifications/TrialEndingSoon.php:50,54` | Use `SubscriptionPrice::perInterval()`; delete `price()` |
| `app/Notifications/TrialEnded.php:59,71` | Use `SubscriptionPrice::perInterval()`; delete `price()` |
| `public/css/site.css` | Add `.eq-price` |

Keep `Billing::getFormattedPrice()` as a thin passthrough — the Blade view calls
`$this->getFormattedPrice()` in three places and Filament pages are the wrong
place to reach into a support class from the template.

The two notifications hardcode `/year` in their action label. Replacing that with
`perInterval()` means the emails follow `subscription.billing_interval` like
everything else, so a future monthly plan cannot ship emails that say "year".

Re-run the notification suite after this, since the action label is asserted:

```
php artisan test --compact --filter=BillingNotificationsTest
```

## Tests

Extend `tests/Feature/Subscription/PublicPagesTest.php`:

- `test_the_pricing_page_is_publicly_reachable` — `/pricing` returns 200 while
  logged out
- `test_the_pricing_page_states_the_price_period_and_tax_treatment` — sees `$27`,
  `per year`, `Tax included`, `merchant of record`
- `test_the_pricing_page_states_the_trial_needs_no_payment_details`
- `test_the_homepage_shows_the_dynamic_price_without_logging_in` — this is the
  attestation; it must fail if someone strips the price off the card again
- `test_every_public_page_links_to_pricing`
- Add `'pricing' => ['/pricing']` to `publicPageProvider()` so the stylesheet,
  contact-address and dead-anchor checks cover it too

New `tests/Unit/SubscriptionPriceTest.php`:

- trims trailing zeros: `27.00` → `$27`
- keeps real cents: `27.50` → `$27.50`
- `perInterval()` follows `subscription.billing_interval`, not a hardcoded year
- a non-USD currency does not render a dollar sign

## Found during implementation

All four original formatters shared a latent bug. `rtrim(rtrim($formatted, '0'), '.')`
strips *any* trailing zero, not just an all-zero fraction, so a price of `27.50`
rendered as **`$27.5`** — a malformed price on the Terms, the billing page and
both trial emails.

It never surfaced because `subscription.price` has always been the whole number
27. `SubscriptionPrice::formatted()` now drops only an exact `.00`, and
`test_real_cents_are_kept` pins it. Worth knowing before anyone sets a price with
cents in it.

## Done when

- [ ] Logged out, the price is reachable from the homepage in one click
- [ ] `/pricing` states amount, period, renewal, tax treatment and trial terms
- [ ] Exactly one expression in the codebase formats the price
- [ ] `php artisan test --compact --filter=PublicPagesTest` green
- [ ] `php artisan test --compact --filter=SubscriptionPriceTest` green
