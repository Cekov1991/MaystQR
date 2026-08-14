# Phase 8 — Legal identity and Terms alignment

**Goal:** the trader named on the site is the same person named on the AgentaOS
account, and the Terms do not contain a clause that is unenforceable against the
customers we are selling to.

**Blocks submission:** yes.
**Status:** ✅ implemented and tested. Suite 272 → 282 tests, 831 → 861 assertions.

> ### ⚠️ The applicant changed after this phase shipped
>
> This phase was written and implemented on the plan of applying to AgentaOS as an
> **individual**, so it renamed `site.company.*` to `site.operator.*` and set the
> operator to *Stefan Cekov*, treating "Mayst Impact" as a brand that belonged
> nowhere near the legal documents.
>
> On **14 August 2026** that decision reversed: the applicant is
> **Mayst Impact DOOEL**, tax number **4032020546119**. Mayst Impact is a registered
> ДОО, and the deciding argument was liability rather than tax — a dynamic QR
> service is a link redirector and therefore a phishing vector by construction, so
> the entity is what keeps a defrauded scanner's claim away from a private person's
> assets. The headline rates in North Macedonia are flat 10% either way, so the
> "save on taxes by going individual" premise did not survive contact with the
> contributions and expense-deductibility questions.
>
> **What the rename got right:** the `operator` abstraction is entity-agnostic, so
> the reversal cost three config values, one sentence in the Privacy Policy, and
> three test assertions. Nothing structural had to move. Read every "an individual"
> in the reasoning below as history, not as the current state.
>
> **One correction to the Tests section:** it describes a
> `test_no_page_refers_to_a_company` asserting `assertDontSee('Mayst Impact')`. No
> such test was ever written. Had it been, this reversal would have broken it — and
> it would have been right to.
>
> Most of this phase shipped early. The `site.company.*` → `site.operator.*` rename
> and the operator naming on both legal pages were pulled forward into
> [Phase 4](./phase-04-privacy-policy.md), because a privacy policy cannot name a
> data controller without them. What landed here: the Terms §11 consumer carve-out,
> the support-email default, the trading-name line, the optional tax number, and the
> footer-credit decision.

---

## Why

### The identity mismatch

The application to AgentaOS is being made by **Stefan Cekov, as an individual** —
not by a company.

The site says otherwise. `config/site.php` defines a `company` block:

```php
'company' => [
    'name'    => env('SITE_COMPANY_NAME', 'Mayst Impact'),
    'address' => env('SITE_COMPANY_ADDRESS', 'Vladimir Komarov 25/4-16, Skopje, North Macedonia'),
],
```

and `terms-and-conditions.blade.php:11` renders *"Operated by Mayst Impact"*.

A reviewer comparing the account holder against the trader named on the site
finds a company name on one side and an individual on the other. That is precisely
the kind of mismatch that stalls a merchant-of-record application, because the
whole question they are answering is *who are we actually selling on behalf of*.

The config comment on that block even asserts the value "must be the registered
name rather than the product name" — correct instinct, wrong conclusion here,
because there is no registered company.

### The jurisdiction clause

`terms-and-conditions.blade.php:138-144` gives North Macedonian courts exclusive
jurisdiction. Against an EU or UK consumer that is unenforceable — a consumer can
sue and be sued in their own country of residence regardless of what the contract
says. So the clause does not gain us anything, and it reads as consumer-hostile to
someone assessing whether we are a safe merchant to underwrite.

The fix is not to remove it. It is to add the carve-out that makes it accurate.

## Edit

### `config/site.php` — rename `company` to `operator`

```php
/*
|--------------------------------------------------------------------------
| Operator
|--------------------------------------------------------------------------
|
| The person or entity legally behind the service: the contracting party in
| the Terms and the data controller in the Privacy Policy. This must match the
| account holder at AgentaOS, who is the merchant of record's counterparty —
| a company name here against an individual there is the mismatch a reviewer
| looks for.
|
| The service is operated by an individual, so this is a legal name, not a
| brand. "Mayst Impact" is a brand and belongs in the footer credit below,
| not in the legal documents.
|
*/

'operator' => [
    'name'    => env('SITE_OPERATOR_NAME', 'Stefan Cekov'),
    'address' => env('SITE_OPERATOR_ADDRESS', 'Vladimir Komarov 25/4-16, Skopje, North Macedonia'),
    'tax_id'  => env('SITE_OPERATOR_TAX_ID'),
],
```

`tax_id` is optional and renders only when set — on both the Terms contact block
and the Privacy Policy controller block.

**Decided by the owner: skip it for now.** Nothing in the AgentaOS form asks for a
trader identifier, so this is optional polish. Setting `SITE_OPERATOR_TAX_ID` at
any point makes the row appear on both pages;
`test_the_legal_pages_show_a_tax_number_only_when_one_is_configured` covers both
branches so the wiring is known to work when it is wanted.

> ⚠️ **Deploy step.** After this rename, `SITE_COMPANY_NAME` and
> `SITE_COMPANY_ADDRESS` in production `.env` are ignored silently. If production
> currently sets `SITE_COMPANY_NAME=Mayst Impact`, the rename is what makes the
> site start saying "Stefan Cekov" — which is the intent, but confirm it rather
> than discover it. Delete the old vars from `.env` so nobody is misled by them
> later.

### `config/site.php` — support email

```php
'support_email' => env('SITE_SUPPORT_EMAIL', 'support@easy-qr-code.com'),
```

The current default is `mayst.impact@gmail.com`, and the block's own comment says
this "should be on our own domain rather than a free mailbox". This address is
cited in the Terms and the Privacy Policy as the legal notice address and is the
only contact route on the site. A Gmail address there is a soft trust signal in
the wrong direction for a paid service.

> ⚠️ Changing the default does nothing if production `.env` still sets the Gmail
> address. Set `SITE_SUPPORT_EMAIL=support@easy-qr-code.com` **and confirm the
> mailbox receives mail** before submitting — Phase 7 routes abuse reports to it
> and the Refund Policy promises a two-business-day response on it.

### `config/site.php` — the footer credit

```php
'credit' => [
    'name' => env('SITE_CREDIT_NAME', 'Mayst Impact'),
    'url'  => env('SITE_CREDIT_URL', 'https://maystimpact.mk'),
],
```

This renders *"Powered by Mayst Impact"* linking off-site
(`layouts/site.blade.php:59-63`). With the operator now named as an individual,
a reviewer sees a personal trader in the legal text and a company credit in the
footer pointing at a different domain.

Not fatal, and it is a legitimate brand credit. But the config already supports
blanking the URL to drop the credit entirely, and the cleanest presentation for a
review is one identity on the page. **Decided by the owner: drop it for launch.** The config default is now unset, so
the credit does not render. `SITE_CREDIT_NAME` is left in place and setting
`SITE_CREDIT_URL` brings the line straight back after approval — no code change.
Two tests cover it: the credit is absent from every public page by default, and it
returns when a URL is configured.

> ⚠️ If production `.env` still sets `SITE_CREDIT_URL=https://maystimpact.mk`, the
> credit will keep rendering. Remove it there.

### Terms and Privacy Policy

| File | Change |
|---|---|
| `terms-and-conditions.blade.php:11` | *"Operated by Stefan Cekov"*; render `operator.tax_id` when set |
| `terms-and-conditions.blade.php:146-151` | Contact block reads `site.operator.*` |
| `privacy-policy.blade.php:141` | Same, plus the controller block from [Phase 4](./phase-04-privacy-policy.md) |
| `refund-policy.blade.php` | No entity reference today — leave it |

Replace `config('site.company.*')` everywhere. `grep -rn "site.company" resources/ app/ tests/`
must come back empty.

### Terms §11 — the carve-out

Keep the existing governing-law and jurisdiction paragraphs, then add:

> Nothing in this section removes any protection you have as a consumer under
> the mandatory law of your country of residence. If you are a consumer in the
> European Union or the United Kingdom, you keep the right to bring proceedings
> in the courts of your own country, and to rely on the consumer protection law
> that applies there.

### Terms §5 — do **not** claim a renewal reminder

While editing the subscription clause, note the temptation. The subscription
renews automatically each year, and there is currently **no pre-renewal
notification** — `app/Notifications/` holds `TrialEndingSoon`, `TrialEnded`,
`RenewalPaymentFailed` and `BillingAlert`, none of which is a renewal reminder.

Do not add wording that promises one. [Phase 10](./phase-10-renewal-notice.md)
builds it; the Terms can describe it once it exists.

## Tests

In `tests/Feature/Subscription/PublicPagesTest.php`:

- Update `test_the_legal_pages_name_the_full_company_address` (line 107) — it sets
  `config(['site.company.address' => ...])`, which stops existing. Rename to
  `..._name_the_operator_address` and point it at `site.operator.address`.
- `test_the_legal_pages_name_the_operator` — set `site.operator.name` to a
  sentinel and assert both the Terms and the Privacy Policy render it. Guards
  against one document being updated and the other not.
- `test_the_terms_preserve_consumer_rights_in_the_eu_and_uk` — sees "country of
  residence"
- `test_no_page_refers_to_a_company` — `assertDontSee('Mayst Impact')` on the
  Terms and the Privacy Policy specifically. The footer credit may still say it,
  so scope this to the legal documents rather than the provider list.
- `test_the_support_email_is_on_our_own_domain` — a unit-ish assertion that
  `config('site.support_email')` does not end in `gmail.com`. Blunt, and it
  catches the one mistake that matters here.

## Found during implementation

**The jurisdiction clause was softened, not just annotated.** The original asserted
*exclusive* jurisdiction in North Macedonian courts. Against an EU or UK consumer
that is unenforceable regardless of what the contract says, so the word was doing no
work while making the document read as consumer-hostile to an underwriter. It now
claims ordinary jurisdiction and adds the carve-out, including the plain statement
that where mandatory local consumer law gives the customer more than these Terms do,
**that law wins**.

**The contact block gained a trading-name line.** "Stefan Cekov, trading as Easy QR
Code" states the relationship between the person on the AgentaOS account and the
brand on the site, which is the exact question the identity check is asking. Cheaper
than making a reviewer infer it.

**Nothing was promised about renewal notices.** Terms §5 still describes automatic
yearly renewal without claiming any advance warning, because
`app/Notifications/` contains no such notification.
[Phase 10](./phase-10-renewal-notice.md) builds it and owns the Terms edit.

## Done when

- [ ] `grep -rn "site.company" .` (excluding `vendor/`) is empty
- [ ] The Terms and Privacy Policy both name Stefan Cekov as the operator and
      controller
- [ ] The name on the site matches the name on the AgentaOS application exactly
- [ ] The Terms preserve EU/UK consumer rights
- [ ] `SITE_SUPPORT_EMAIL` is set in production and the mailbox receives mail
- [ ] A decision has been made on the footer credit
- [ ] `php artisan test --compact --filter=PublicPagesTest` green
