# Anonymous event spine + static-QR offer — Implementation Plan

An anonymous counter for the funnel from "stranger lands on the homepage" to
"subscription row", and the offer card that funnel exists to measure. Every step
already happened in code we owned; none of it was ever written down, so questions
as basic as "how many people generate a free code and then look at the price" had
no answer.

**Status:** phases 1–3 implemented, tested and committed on `worktree-event-spine-and-offer`; phases 4–6 outstanding.
**Related:** [subscription-implementation-plan.md](./subscription-implementation-plan.md) · [CONTEXT.md](../CONTEXT.md)

> Written down after the fact. The plan originally lived only in a chat
> transcript, which is how phase 3 came to be built with a throttle three times
> looser than the one agreed and how phase 2 came to be reported as "unknown"
> when it was pruning all along. Keep this file current instead.

---

## The design in one paragraph

`site_events` counts occurrences and never who they belonged to. No user id, no
session id, no IP, no user agent, and `context` limited to a handful of known
labels — enforced by `EventPrivacyTest`, because section 2 of the Privacy Policy
says we build no profile of the pages you visit and a single identifier column
would make that false. Where per-person attribution is genuinely needed it lives
on the user's own row as `users.signup_source`: one durable fact about an account
rather than a stream of behaviour. Page views are deliberately absent — the
highest-volume row available, the only candidate with no action behind it, and
precisely what the policy disclaims.

## Decisions

| Area | Decision |
|---|---|
| Naming | Table `site_events`, model `App\Models\SiteEvent`, enum `App\Enums\TrackedEvent`. Behaviour on the enum (`TrackedEvent::StaticQrGenerated->record()`), matching how `BillingInterval` carries `configured()`. |
| Retention | 90 days, from `config('site.event_retention_days')`, rendered into the Privacy Policy so the published commitment cannot drift from what `events:prune` enforces. |
| Page views | **Out.** Actions only. |
| Identifier columns | **None, ever.** The guard test hardcodes it; reversing it means a policy rewrite. |
| Trust split | `TrackedEvent::isClientLoggable()`. A new case is untrusted by default and must be named to become browser-reportable. |
| Dismissal state | `localStorage`, not a cookie. A new marketing cookie would contradict section 2c and force a real consent gate. |
| Offer attribution | `?ref=` on the register URL, allowlisted to known values, persisted to `users.signup_source`. Not a session flag — phase 5's arm rides in the URL so no profile is needed. |
| Holdout | **Unresolved.** Plan says 50%; the operator leaned toward 0% (show everyone) on the grounds that with no users yet, a holdout means most early users never see the card. See phase 5. |

---

## Phase 1 — The spine *(done, `8691857`)*

Server-side only, no UI.

- `config/site.php` — `event_retention_days`, env-backed, default 90, beside `scan_retention_months`.
- Migration `create_site_events_table` — `id`, `name` (indexed), `variant` (nullable), `context` (nullable json), `occurred_at` (indexed). No identifier columns. No `softDeletes()` — the trap `PruneScans` documents, where declared soft deletes the model never used left the prune looking optional.
- `app/Enums/TrackedEvent.php` — string-backed, 8 cases, `record(?string $variant, ?array $context): void`.
- `app/Models/SiteEvent.php` + factory — enum cast on `name`, array cast on `context`, `named()`/`since()`/`today()` scopes, `$timestamps = false`.
- The four server-side moments already in code: `InstantQrController::generate` → `StaticQrGenerated`; `SubscriptionController::checkout` → `CheckoutStarted`; `billing.success` → `CheckoutCompleted`; `billing.cancel` → `CheckoutAbandoned`.

**Tests** — `EventRecordingTest` (11), `EventPrivacyTest` (13 after phase 3).
The negative ones carry the weight: a rejected URL is not counted (protects the
denominator), a failed AgentaOS checkout is not counted as a checkout started (an
outage would otherwise read as buyer abandonment), the URL you typed never
reaches a row, and loading a public page writes nothing at all.

**Also in this commit:** the homepage said "Nothing is stored" in two places,
which counting a generation made imprecise even though nothing about the code
itself is kept. It now says "we never store your code or its link", guarded by a
test so the absolute claim cannot return.

## Phase 2 — Pruning, so the policy can be true *(done, in `8691857`)*

Committed together with phase 1 rather than separately, because the phase
boundary had been lost by then.

- `app/Console/Commands/PruneEvents.php` — mirrors `PruneScans`: `--dry-run`, `CHUNK = 1000`, refuses a window under 1 day, chunked deletes so a bulk delete never holds locks while the homepage waits behind it.
- `routes/console.php` — `events:prune` daily at 05:00, clear of `scans:prune` at 04:00, `withoutOverlapping()`.
- `resources/views/privacy-policy.blade.php` — section 2b's flat "we run no analytics" claim replaced with an accurate disclosure of the anonymous counts and the 90-day window; a retention-table row added.

**Tests** — `EventPruningTest` (7), `EventDisclosureTest` (5).

## Phase 3 — The public endpoint *(done, `d47f896`)*

Four events leave no trace on the server — a download is a click on a `data:` URI
that never reaches us, and the offer being shown, dismissed or clicked all happen
inside one already-rendered page. So the page reports them, which makes this the
one path where a stranger can cause a row to be written.

- `TrackedEvent::isClientLoggable()` — the trust split. Without it anyone could POST `checkout_completed` in a loop and ruin the only record we have of revenue, undetectably, since there is no identifier on these rows to spot a flood with.
- `routes/web.php` — `POST /events`, `throttle:30,1`, in the `web` group so a forged count must come from something that first loaded a page of ours. The throttle sits ahead of validation, so garbage payloads are counted too.
- `app/Http/Controllers/SiteEventController.php` + `app/Http/Requests/LogSiteEventRequest.php` — returns `204`.
- `resources/views/home.blade.php` — a `logEvent()` helper and download-click listeners. Event names are rendered from the enum, never typed, so a renamed case cannot leave the page posting a dead value.

**Tests** — `ClientEventLoggingTest` (21). The load-bearing ones are enum-driven
data providers: every client-loggable case must be accepted, every server-only
case must 422 and write nothing, so a newly-flagged case is covered the moment
someone adds it.

**Divergences from the original plan**, recorded so they are not mistaken for the
plan itself:

| Planned | Built | Why |
|---|---|---|
| `throttle:30,1` | `throttle:100,1`, since **reverted to 30** | The 100 came from multiplying the generator's 20-a-minute abuse cap by four events, which sizes the endpoint for a flood rather than a person. |
| `EventController` / `StoreEventRequest` / `EventEndpointTest` | `SiteEventController` / `LogSiteEventRequest` / `ClientEventLoggingTest` | There is no `Event` model; the model is `SiteEvent`, and the repo names controllers after what they handle. Kept. |
| Request field `name` | Request field `event` | `{"name": …}` reads like a label. Kept. |
| `variant` validated against a short allowlist | `variant` **prohibited** | The allowlist needs arm names that do not exist until phase 5. The endpoint is identical by the end of phase 5 and stricter in between; the rule carries a comment handing phase 5 the replacement. Kept. |

---

## Phase 4 — The offer *(next)*

- `resources/views/home.blade.php` — the offer block inline inside `#static-result`, revealed on download click **after** the download starts. Built from the existing `.eq-card` / `.eq-price` / `.eq-panel` / `.eq-btn` classes so `public/css/site.css` needs little or nothing new. This is `eq-*` CSS, not Tailwind: Tailwind lives only in the Filament admin.
- It sits **alongside** the result panel's existing quiet "A static code can never be changed… Create a dynamic QR" line, which stays. Replacing it would destroy the comparison phase 5 exists to make: that link gets `?ref=static-inline` there, and the whole question is whether the loud offer converts better than the quiet link.
- Dismissal in `localStorage`. Note the consequence: dismissal is per-browser and invisible to us, and without it `offer_dismissed` becomes noise as people dismiss the same card repeatedly.
- All copy interpolated — `config('subscription.trial_days')`, `SubscriptionPrice::formatted()`. The monthly figure derived, never hardcoded, and never shown without the annual charge beside it: a monthly number alone misdescribes an annually-billed product, and `PublicPagesTest` guards the pricing claims AgentaOS reviews.
- CTA → register with `?ref=static-offer`.
- Migration `add_signup_source_to_users_table` (nullable string) + capture in the Filament registration path, allowlisted to known ref values so it cannot become a free-text sink.
- `resources/views/privacy-policy.blade.php` — one line in section 2a: at registration we also record which part of the site you came from.

**Tests** — `tests/Feature/StaticOfferTest.php`. The homepage has no test of its
own beyond the event ones, so this is also its first real coverage.
- Homepage renders; the generator returns PNG + SVG data URIs.
- The offer block is present with the trial days and price from config.
- A registration carrying `?ref=static-offer` persists `signup_source`; an unknown ref persists null.

## Phase 5 — The holdout *(decide before phase 4 ships)*

Random suppression, `OfferShown` still logged with `variant=holdout` for the
suppressed half, and the existing quiet inline link gets `?ref=static-inline`.
Registration rates by ref then answer whether the offer *caused* anything — with
no identifier, because the arm travels in the URL rather than in a profile.
Replaces the `prohibited` variant rule from phase 3 with `Rule::in` over the arm
names.

The unresolved question is the rate. The plan proposed 50%. The operator's
objection is that with no users yet, a 50% holdout means most early users never
see the card at all. The counter-argument is that it is cheap now and impossible
later: once the offer ships to everyone the baseline is gone permanently.

## Phase 6 — Reading it

`php artisan funnel:report {--days=30}` — one table: QRs generated → offers shown
→ clicked → registered by source → subscribed. No new authenticated UI surface,
nothing to leak, and it is the artefact that says whether any of this was worth
building.
