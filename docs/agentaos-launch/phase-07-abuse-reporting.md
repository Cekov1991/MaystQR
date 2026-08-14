# Phase 7 — Abuse reporting

**Goal:** anyone who encounters a malicious QR code pointing at our domain can
report it, and we can act on it.

**Blocks submission:** no. This is the strongest *positive* signal in the plan.

---

## Why

The go-live form asks us to attest that our product *"does not engage in high-risk,
shady, or illegitimate use of technology"*, and to confirm we are not *"enabling
spam, fraud or harassment"*.

A dynamic QR code service is, structurally, a link shortener with a printed
front end. That makes it a known phishing vector — "quishing" — and it is the
first thing a merchant-of-record risk reviewer will think about when they read
what we do. Every `/q/{shortUrl}` on our domain carries our domain's reputation
to wherever the owner points it, and the owner can repoint it **after** the code
is printed and scanned.

Terms §4 already prohibits phishing, malware and linking to illegal content. But
there is no way for anyone to tell us it is happening. A prohibition with no
reporting channel is a policy we cannot enforce, and a reviewer will read it that
way.

Adding one changes the story from "we forbid abuse" to "we forbid abuse, here is
how it gets caught, here is what we do about it". That is a materially different
application.

## New

### `app/Http/Controllers/AbuseReportController.php`

Two actions:

- `create()` — renders the form
- `store(StoreAbuseReportRequest $request)` — notifies support, redirects back
  with a confirmation

Public, no auth. The people who need it are strangers by definition.

### `app/Http/Requests/StoreAbuseReportRequest.php`

Follows `app/Http/Requests/ProfileUpdateRequest.php` for structure and rule style
(check whether it uses array or string rules and match it).

| Field | Rules | Notes |
|---|---|---|
| `code_url` | required, string, max 2048 | The `/q/xxxx` link or the full URL. Do not validate it as a URL — someone reading a printed poster may type a partial reference, and rejecting them loses the report |
| `reason` | required, in: phishing, malware, illegal_content, spam, harassment, other | A fixed list makes reports triageable |
| `details` | required, string, min 20, max 5000 | |
| `reporter_email` | nullable, email | Optional on purpose — requiring contact details suppresses reports, and we do not need to reply to act |

Custom messages per the project convention that Form Requests carry both rules
and messages.

### `app/Notifications/AbuseReported.php`

Standard shape: `extends Notification implements ShouldQueue`, `use Queueable`,
`via()` returns `['mail']`, a `toMail(): MailMessage`.

Dispatched on demand to the support address, exactly as
`app/Services/BillingAlerts.php:37` already does:

```php
Notification::route('mail', config('site.support_email'))
    ->notify(new AbuseReported($report));
```

The mail should carry the reported URL, the reason, the details, the reporter's
email if given, and — resolved at send time — **which account owns the code and
where it currently points**. A report we have to go look up by hand is a report
that waits; a report that arrives with the owner and destination attached can be
acted on immediately.

Resolve the owner by `short_url` against `QrCode`. If it does not resolve, say so
in the mail rather than failing — a mistyped or already-deleted code is still
worth knowing about.

### `resources/views/report.blade.php`

Extends `layouts.site`, reuses `eq-card`, `eq-input`, `eq-label`, `eq-btn`,
`eq-error`. Plain form post, no JS — this page must work for someone in a hurry
on a phone.

Open with what it is for and set the expectation honestly:

> **Report a QR code**
>
> If a QR code on our domain leads somewhere harmful — a phishing page, malware,
> illegal content, or harassment — tell us and we will investigate. You do not
> need an account, and you do not have to give us your email address.
>
> We review every report. Codes that break our Terms are disabled, and the
> accounts behind them can be suspended. If you have given us an email address
> we will tell you what we did.

Only promise what we will do. "We review every report" is true if we read the
mailbox. Do not promise a response time we have not committed to elsewhere.

### Route

```php
Route::get('report', [AbuseReportController::class, 'create'])->name('report.create');

Route::post('report', [AbuseReportController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('report.store');
```

5 per hour per IP. Generous for a real reporter, and it stops the form being used
to flood our own support mailbox — the form sends mail to us on an unauthenticated
request, so it is an open relay to our inbox if left unthrottled.

## Edit

| File | Change |
|---|---|
| `resources/views/layouts/site.blade.php:53-58` | Footer link: **Report a QR code** |
| `resources/views/terms-and-conditions.blade.php:47-55` | Add the clause below to §4 |

Terms §4 addition:

> Anyone can report a QR code that breaks these rules at
> [{{ config('site.domain') }}/report]({{ url('/report') }}). We investigate every
> report. Where we find a code pointing at phishing, malware, illegal content or
> material intended to harass, we disable it without notice and may suspend the
> account behind it. Because a dynamic code's destination can be changed after
> printing, we may disable a code based on where it points at the time we review
> it, regardless of where it pointed when it was created.

That last sentence is the one worth having. It reserves the right we actually
need — and demonstrates we understand our own abuse surface.

## Tests

New `tests/Feature/AbuseReportTest.php`:

- `test_the_report_form_is_publicly_reachable` — 200 while logged out
- `test_a_report_notifies_support` — `Notification::fake()`, assert
  `AbuseReported` was sent on the mail channel to the configured support address
- `test_a_report_identifies_the_code_owner_and_destination` — create a `QrCode`
  via factory, report its `short_url`, assert the notification carries the owner
  and current destination. This is the part that makes reports actionable, so it
  is the part worth pinning.
- `test_a_report_for_an_unknown_code_is_still_delivered`
- `test_a_report_requires_a_url_a_reason_and_details`
- `test_a_report_rejects_a_reason_outside_the_list`
- `test_a_report_may_omit_the_reporter_email`
- `test_reports_are_rate_limited` — 6 posts in an hour, the sixth gets 429
- `test_the_footer_links_to_the_report_form` — add to `PublicPagesTest`, driven by
  `publicPageProvider()`
- `test_the_terms_describe_the_abuse_reporting_route` — in `PublicPagesTest`

Add `'report' => ['/report']` to `publicPageProvider()` so the stylesheet and
contact-address checks cover it.

## Done when

- [ ] `/report` is reachable from every public page's footer
- [ ] A submitted report arrives at the support address with owner and
      destination resolved
- [ ] The form is throttled
- [ ] Terms §4 describes the route and what we do with reports
- [ ] `php artisan test --compact --filter=AbuseReportTest` green
- [ ] Submit one real report end to end against production and confirm the mail
      lands — this path is only useful if the mailbox actually receives it
