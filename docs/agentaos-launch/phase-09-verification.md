# Phase 9 — Verification before submitting

**Goal:** every attestation on the go-live form is verified against the live
production site, not against the codebase or against intent.

**Blocks submission:** yes — this *is* the submission gate.

---

## Why

Phases 1–8 change code. This phase checks the thing the reviewer will actually
look at: `easy-qr-code.com`, in a browser, logged out, in production.

Tests passing locally proves the code is right. It does not prove the deploy
landed, that `.env` was updated, that the cron entry exists, or that the mailbox
receives mail. Every one of those is a way to pass the suite and fail the review.

## Code gate

```
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

The suite was **192 tests / 584 assertions** green before this plan (measured on
`dev` at `ce47257`, not the stale 148 figure from an earlier session). It must be
green after, with the new tests added by each phase:

| After phase | Tests | Assertions |
|---|---|---|
| Baseline (`ce47257`) | 192 | 584 |
| 1 — public pricing | 212 | 631 |
| 2 — remove parked landing | 227 | 691 |

Pint note: this project's Pint build does not support `--format agent`. Run
`vendor/bin/pint --dirty` instead.

Also confirm nothing was left behind:

```
grep -rn "Google Analytics\|gtag" app/ resources/ config/     # Phase 3 — empty
grep -rn "googleapis\|gstatic" resources/                      # Phase 3 — empty
grep -rn "site.company" app/ resources/ tests/                 # Phase 8 — empty
grep -rn "ip_address" app/                                     # Phase 5 — sessions only
grep -rn "landing-page\|WelcomeController" routes/ app/        # Phase 2 — empty
```

## Deploy gate

- [ ] Deployed to production
- [ ] `SITE_SUPPORT_EMAIL=support@easy-qr-code.com`, and a test mail to it arrives
- [ ] `SITE_COMPANY_NAME` / `SITE_COMPANY_ADDRESS` removed from `.env`;
      `SITE_OPERATOR_*` set or intentionally left on defaults
- [ ] Footer credit decision applied (`SITE_CREDIT_URL`)
- [ ] Migrations ran — `qr_code_scans` has no `ip_address`
- [ ] `php artisan schedule:list` shows `billing:sync`, `billing:notify` and
      `scans:prune`
- [ ] The `schedule:run` cron entry exists on Laravel Cloud. Without it Phase 6
      never runs, and the retention period published in the Privacy Policy is
      false in production while every test passes locally.
- [ ] `npm run build` if any Filament-side styling changed

## Live site walkthrough

Do this **logged out, in a private window**, as a reviewer would.

### Pricing — the attestation that was false

- [ ] Land on `easy-qr-code.com`. The price of the paid product is visible or one
      click away.
- [ ] `/pricing` states: `$27`, per year, renews automatically, tax included,
      merchant of record, 7-day trial, no card required
- [ ] Nowhere does the site describe dynamic QR codes as free

### Reachability

Every one of these returns 200 while logged out:

- [ ] `/`
- [ ] `/pricing`
- [ ] `/terms-and-conditions`
- [ ] `/privacy-policy`
- [ ] `/refund-policy`
- [ ] `/report`

And:

- [ ] `/landing-page` returns 404
- [ ] Each of the above is linked from the footer — a page a reviewer cannot find
      is a page they will record as missing

### No false claims

- [ ] No testimonial, star rating, review, user count, "trusted by" strip, or
      years-of-experience claim anywhere
- [ ] View source on the homepage and the privacy policy: no request to any
      Google domain, no `gtag`, no analytics tag of any kind
- [ ] The cookie notice describes only the cookies actually set, and offers no
      choice we do not honour
- [ ] Nothing claims QR codes are encrypted

### Identity

- [ ] The Terms name **Stefan Cekov** as operator
- [ ] The Privacy Policy names **Stefan Cekov** as data controller
- [ ] That name matches the AgentaOS application exactly
- [ ] The address and support email are identical across both documents
- [ ] The Privacy Policy says data is hosted in the **United States** and names
      Laravel Cloud, Cloudflare, Resend and AgentaOS

### Functional

- [ ] Generate a static QR from the homepage; scan it with a phone; it resolves
- [ ] Register, land in the panel, and confirm the trial banner shows 7 days
- [ ] Create a dynamic code, scan it, confirm it resolves and records a scan
- [ ] Open that code's Scans tab: country, device, OS, browser present; **no IP
      column available, even via the toggle menu**
- [ ] Submit a real abuse report through `/report` and confirm the mail arrives
      with owner and destination resolved
- [ ] Run a live checkout in AgentaOS test mode and confirm the entitlement lands

The checkout run matters because a reviewer may test a purchase. A broken
checkout at review time is a rejection regardless of how good the legal pages are.

## Form answers to re-confirm at submission

| Question | Answer | Still true? |
|---|---|---|
| Usage claims or reviews displayed? | No, there are none | Verified above |
| Existing paying customers? | No, I'm just starting out | Only if no live sale has happened yet — **re-check at submission** |
| Product name infringes a trademark? | No | "Easy QR Code" is descriptive; no known conflict |
| Pricing accessible before purchase? | Yes | Phase 1 |
| Publicly accessible Privacy Policy? | Yes | Phases 3–6 |
| Publicly accessible Terms of Service? | Yes | Phase 8 |
| High-risk or shady use of technology? | No | Phase 7 evidences it |

> ⚠️ The "existing customers" answer is time-sensitive. It is honest today. If
> anyone has paid by the time the form is submitted, the honest answer changes to
> yes. The form's own WRONG column calls out selecting "Yes" without sales; the
> reverse is equally a false statement.

## Done when

- [ ] Pint clean, full suite green
- [ ] Every box above checked against production
- [ ] The go-live form submitted
