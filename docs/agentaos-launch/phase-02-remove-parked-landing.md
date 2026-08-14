# Phase 2 — Remove the parked landing page

**Goal:** no publicly reachable page contradicts our pricing, our technology or
our first go-live attestation.

**Blocks submission:** yes.

---

## Why

`routes/web.php:18` publicly serves the previous marketing page:

```php
Route::view('/landing-page', 'welcome')->name('landing');
```

It is indexable — `public/robots.txt` is `Disallow:` with nothing after it, and
the page sets no `noindex`. Everything below currently renders to anyone who
finds the URL:

| Line | Content | Problem |
|---|---|---|
| `welcome.blade.php:3` | title *"Free & Powerful Dynamic QR Code Solutions"* | Dynamic codes cost $27/year |
| `:44` | *"Dynamic QR Code Generator: Free & Powerful Solutions"* | Same |
| `:50` | *"For Free"* as the hero accent | Same |
| `:163` | *"Free solutions for individuals, SMBs, and enterprises."* | Same, plus we have no enterprise tier |
| `:269` | *"Dynamic QR codes are secure and encrypted. They use advanced encryption algorithms to protect your data."* | Simply false. A QR code is an encoding, not a cipher. |
| `:67-77` | *"12,000+ happy customers"* with five stock avatars | Fake social proof, with **zero** paying customers |
| `:121-124` | *"15+ Years of experience in business service"* | Fake |

The two fake-proof blocks are inside `{{-- --}}` comments, so they do not render
today. That is the only reason the *"no usage claims or reviews"* attestation is
currently true — it is true by accident, one uncomment away from being false.
The pricing and encryption claims are **not** commented and render live.

A reviewer who finds this page sees a product advertised as free that we are
asking them to help us charge for.

## Delete

| Kind | File | Why it is safe |
|---|---|---|
| View | `resources/views/welcome.blade.php` | The page itself |
| Layout | `resources/views/layouts/qr.blade.php` | Only `welcome.blade.php` extends it |
| Controller | `app/Http/Controllers/WelcomeController.php` | Dead — no route references it |

Verified: `grep -rn "WelcomeController" routes/ app/` finds only the class
declaration, and `route('landing')` is referenced nowhere.

> ⚠️ **`public/landing/assets/` must stay.** `layouts/site.blade.php:18-19`
> pulls the favicon and apple-touch-icon from `landing/assets/img/`. The 9.7MB
> of Bootstrap and template vendor assets in there become unreferenced, but
> clearing them is a separate cleanup with its own risk of taking the favicon
> with it. Out of scope here.

## Edit

| File | Change |
|---|---|
| `routes/web.php:16-18` | Remove the route and its explanatory comment |
| `resources/views/partials/cookie-banner.blade.php:2-4` | The comment says the partial is included by "both `layouts.site` and the parked `layouts.qr`". After this phase only `layouts.site` includes it — and the inline-styles justification no longer holds, though leaving the styles inline is harmless |

`route('welcome')` stays as-is — it points at `/` (`routes/web.php:10`), not at
the deleted page, and `qr/inactive.blade.php:64` and `layouts/site.blade.php:35`
both depend on it.

## Tests

In `tests/Feature/Subscription/PublicPagesTest.php`:

- **Delete** `test_the_parked_landing_page_still_renders_without_the_package_model`
  (line 31) — it asserts the page we are removing returns 200
- **Add** `test_the_parked_marketing_page_is_gone` — `/landing-page` returns 404.
  This is the regression guard: it fails if anyone restores the route.
- **Add** `test_no_public_page_advertises_dynamic_codes_as_free` — walk
  `publicPageProvider()` and assert none of them says `For Free` or
  `Free & Powerful`
- **Edit** the docblock at line 61-64: *"Only the parked landing page may still
  reference them"* is no longer true of any page, and the `assertDontSee` checks
  for `landing/assets/vendor` now hold everywhere unconditionally

The dead-anchor test at line 82 (`#about`, `#features`, `#faq`) already passes
for the remaining pages — those anchors only ever existed on the deleted page,
so that test becomes trivially true. Keep it; it costs nothing and documents why
the footer must not grow those links back.

## Done when

- [ ] `/landing-page` returns 404
- [ ] No page anywhere describes dynamic QR codes as free
- [ ] The word "encrypted" appears nowhere in reference to QR codes
- [ ] No fake customer count or years-of-experience claim exists in the
      codebase, commented out or otherwise
- [ ] The favicon still loads on every public page
- [ ] `php artisan test --compact --filter=PublicPagesTest` green
