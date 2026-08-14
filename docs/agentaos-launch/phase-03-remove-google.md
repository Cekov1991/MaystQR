# Phase 3 — Remove Google from the public site

**Goal:** the site stops describing tracking it does not run, and stops silently
shipping visitor IPs to a third party it does not disclose.

**Blocks submission:** yes.
**Status:** ✅ implemented and tested. Suite 227 → 244 tests, 691 → 746 assertions.
Fonts went to Bunny; self-hosting remains available as the stronger option.

---

## Why

Two separate problems, both involving Google, pointing in opposite directions.

### 1. We describe analytics we do not have

`privacy-policy.blade.php` devotes a whole section to Google Analytics:

| Location | Claim |
|---|---|
| §2c, line 45 | *"Analytics: Google Analytics cookies to understand how visitors use our site"* |
| §3, lines 49-62 | An entire section on Google Analytics, what it collects, US storage, opt-out add-on |
| §4, line 73 | *"Analyse usage trends (via Google Analytics)"* |
| §6, line 90 | *"Google Analytics data is stored according to Google's retention settings (we currently use 26 months)"* |
| §7, line 96 | *"Google (via Google Analytics)"* listed as a data recipient |
| §8, line 108 | Right to *"withdraw consent for ... analytics tracking"* |
| `cookie-banner.blade.php:15` | *"We use essential cookies to run the site and Google Analytics to understand how it is used."* |

**There is no Google Analytics installed.** No `gtag`, no `googletagmanager`,
no measurement ID in any view. A reviewer reading the policy and then the page
source finds a consent banner asking permission for tracking that does not exist.

This is the same category of defect as a fake testimonial — the site states
something untrue about itself. It also makes the consent banner's Decline button
meaningless: `cookies/decline` (`routes/web.php:63-65`) sets a cookie and hides
the banner. It suppresses nothing, because there is nothing to suppress.

Stripping is the right fix, not installing GA. We can add analytics later with an
honest disclosure and a Decline button that actually gates the tag.

### 2. We ship every visitor's IP to Google without disclosing it

`layouts/site.blade.php:21-23` hotlinks Google Fonts:

```html
<link href="https://fonts.googleapis.com" rel="preconnect">
<link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:...&family=Inter:..." rel="stylesheet">
```

Every visitor to the homepage, the pricing page and **the privacy policy itself**
transmits their IP address to Google to fetch a font. That is a disclosed-processor
gap and, in the EU, a well-litigated one.

The rest of the app already gets this right — `layouts/app.blade.php:11-12` and
`layouts/guest.blade.php:11-12` both use `fonts.bunny.net`, which is EU-operated
and logs nothing. Only the new public layout regressed to Google.

## Edit

### Privacy policy

Remove every row in the table above. Section 3 goes entirely; sections 4-12
renumber to 3-11.

Do **not** simply delete the cookie section — replace §2c with what is actually
true:

> **c) Cookies**
>
> We use only the cookies needed to run the site: a session cookie that keeps
> you signed in, a security cookie that protects forms against cross-site
> request forgery, and a cookie that records that you have seen the notice
> below. We do not use advertising or analytics cookies, and we run no
> third-party tracking.

Phase 4 rewrites the rest of this document. Phase 3 only removes the false
Google claims, so the two phases can land independently and the policy is never
in a half-edited state.

### Cookie banner

`resources/views/partials/cookie-banner.blade.php` — reword and drop to one button:

> We use only essential cookies needed to run this site and keep you signed in.
> No tracking, no advertising. See our Privacy Policy. **[ Got it ]**

Strictly necessary cookies do not require consent, so an Accept/Decline pair is
misleading: it implies a choice that does not exist and that the Decline button
does not honour. One acknowledgement button is both honest and simpler.

### Routes

Remove `cookies/decline` (`routes/web.php:63-65`). Only the banner referenced it,
and after the rewrite nothing does. Keep `cookies/accept` — it is what dismisses
the notice.

### Fonts

`layouts/site.blade.php:21-23` — repoint Manrope and Inter at `fonts.bunny.net`,
matching `layouts/app.blade.php`. One-line change, removes Google as a processor,
and keeps the project consistent with itself.

> **Stronger option:** self-host both families as `woff2` under `public/fonts/`
> with `@font-face` rules in `site.css`. That removes the font CDN as a processor
> entirely rather than swapping it for a friendlier one, and removes a render-blocking
> external request. More work; better answer. Take it if there is time.

Whichever route is chosen, Phase 4's processor list must match it.

## Tests

In `tests/Feature/Subscription/PublicPagesTest.php`, driven by `publicPageProvider()`:

- `test_no_public_page_mentions_google_analytics` — `assertDontSee('Google Analytics')`
  and `assertDontSee('gtag')`. This is the guard: it fails the moment someone
  re-adds the claim without re-adding the tag, or vice versa.
- `test_no_public_page_loads_assets_from_google` — `assertDontSee('googleapis.com', false)`
  and `assertDontSee('gstatic.com', false)`
- `test_the_cookie_notice_describes_only_essential_cookies` — sees "essential",
  does not see "Google", offers no Decline control
- `test_the_privacy_policy_still_explains_the_cookies_we_do_set` — the session,
  CSRF and consent cookies are named. Removing the false claim must not leave
  the real cookies undisclosed.

Note the QR content views legitimately link to `google.com/maps` and
`calendar.google.com` (`qr/location.blade.php:35`, `qr/calendar.blade.php:20`).
Those are user-initiated outbound links on scan pages, not embedded assets, and
they are not in `publicPageProvider()`. Scope the assertions to the provider
pages so these are not caught.

## Found during implementation

**Bunny serves everything we asked for — verified, not assumed.** Fetching
`https://fonts.bunny.net/css?family=manrope:500,700,800|inter:400,500,600&display=swap`
returns both families at all six weights (400, 500, 600, 700, 800), across
latin, greek and cyrillic subsets. No fallback risk.

**The privacy policy's own cookie section had to be rewritten, not just cut.**
Deleting the Google Analytics bullet from §2c would have left "Essential
functions" and "Preferences" describing cookies without saying that no tracking
happens at all. The replacement names the three cookies we actually set — session,
CSRF, and the consent-notice cookie — and states plainly that there is no
third-party tracking. `test_the_privacy_policy_still_discloses_the_cookies_we_do_set`
guards that the removal did not create a new omission.

**One test had to be narrowed.** An early `assertDontSee('encrypted')` from Phase 2
would have caught the policy's truthful "Encrypted passwords" line. Any test in
this family must target the specific false claim, not the vocabulary around it.

**Section renumbering.** Removing §3 shifted everything below it: old 4–12 are now
3–11. Nothing links to a numbered section internally, so there were no anchors to
fix — but [Phase 4](./phase-04-privacy-policy.md) restructures the document again
and should be written against the current numbering, not the original.

## Done when

- [ ] "Google Analytics" appears nowhere in the codebase
- [ ] No public page requests an asset from a Google domain
- [ ] The cookie notice describes exactly the cookies we set, and offers no
      choice we do not honour
- [ ] `cookies/decline` is gone
- [ ] Fonts still render correctly on every public page (visual check — the
      heading font is load-bearing for the layout)
- [ ] `php artisan test --compact --filter=PublicPagesTest` green
