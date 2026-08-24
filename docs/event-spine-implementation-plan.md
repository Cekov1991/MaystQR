# Anonymous event spine + static-QR offer — Implementation Plan

An anonymous counter for the funnel from "stranger lands on the homepage" to
"subscription row", and the offer card that funnel exists to measure. Every step
already happened in code we owned; none of it was ever written down, so questions
as basic as "how many people generate a free code and then look at the price" had
no answer.

**Status:** phases 1–6 implemented, tested and committed on `worktree-event-spine-and-offer`. Nothing pushed or deployed.
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
| Holdout | **Declined, 0%.** Everyone sees the offer. With no users yet, holding it back from half of them means most early visitors never see it. The comparison runs through `SignupSource` instead — see phase 5. |
| Dismissal reach | Per-browser and invisible to us, accepted. The same person is asked again on another device; the alternative is a consent banner. |

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

## Phase 5 — The comparison, without a holdout *(done)*

**The holdout was declined.** The proposal was to suppress the offer for half of
all visitors and log `OfferShown` with `variant=holdout` for the suppressed half,
which would have answered whether the offer *caused* registrations that would
not otherwise have happened. With no users yet, that means most early visitors
never see the offer at all, and the operator judged that too high a price.

What replaced it answers a narrower question that is arguably more useful, and
answers it today:

- `SignupSource::StaticInline` — the quiet "Create a dynamic QR" line in the result panel now carries `?ref=static-inline` beside the offer's `?ref=static-offer`.
- Both are rendered for every visitor. Comparing their registration rates asks whether a loud offer converts better than an unobtrusive line of text, with nothing suppressed for anybody.

**What this cannot answer**, stated so nobody mistakes one question for the
other: whether the offer produced registrations that would not have happened at
all. That needs a held-back group. It also needs something the current schema
does not have — the arm carried through to registration, since `signup_source`
records which link was followed and not which arm the visitor was in. Reversing
this decision therefore means building both, not just turning a percentage up.

Consequently no event carries a `variant`, and `LogSiteEventRequest` keeps
`variant` **prohibited** permanently rather than pending: a column whose only
value is "everyone" carries no information. `StaticOfferTest` asserts no arm
reaches the table, so adding suppression without adding the endpoint's allowlist
fails loudly.

## Phase 6 — Reading it *(done)*

`php artisan funnel:report {--days=30}` — two tables. The first is the anonymous
counts: generated → downloaded → offer shown → clicked/dismissed, then checkouts
opened/completed/abandoned. The second is registrations by `signup_source` and how
many of each went on to pay, which is the offer-versus-quiet-link comparison from
phase 5.

A command rather than a dashboard: no authenticated surface to get wrong, nothing
to leak, and no new page to keep in step with the Privacy Policy.

The presentation carries three caveats because the data genuinely has them, and
each is asserted by a test rather than left to a reader's goodwill:

- **The stages are not a cohort.** With no identifier on the event rows these are independent totals over one window, so a rate is a ratio of two counts and can exceed one hundred per cent at low volume. That is the privacy design working, not a bug in the counter.
- **Half the report is forgeable.** The offer figures are browser-reported up to the endpoint throttle; the registration and subscription figures come from account rows and are exact. The report says which is which.
- **Zero over zero is an em dash, not 0%.** A genuine zero rate still prints as `0%`. Conflating the two invents a finding out of an empty window, which is the likeliest way this gets misread on its first run.

Asking for a window longer than `event_retention_days` is allowed and warns,
because the account half of the report still covers the full window while the
event half stops wherever `events:prune` got to.
