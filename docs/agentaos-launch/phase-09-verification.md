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
vendor/bin/pint --dirty
php artisan test --compact
```

> This project's Pint build rejects `--format agent`, the flag `CLAUDE.md`
> specifies. Use the plain `--dirty` form.

The suite was **192 tests / 584 assertions** green before this plan (measured on
`dev` at `ce47257`, not the stale 148 figure from an earlier session). It must be
green after, with the new tests added by each phase:

| After phase | Tests | Assertions |
|---|---|---|
| Baseline (`ce47257`) | 192 | 584 |
| 1 — public pricing | 212 | 631 |
| 2 — remove parked landing | 227 | 691 |
| 3 — remove Google | 244 | 746 |
| 4 — privacy policy | 256 | 795 |
| 5 — remove scanner IP | 262 | 807 |
| 6 — scan retention | 272 | 831 |
| 8 — legal identity and Terms | 282 | 861 |
| 7 — abuse reporting | 307 | 948 |
| Mail branding (out of plan) | 318 | 1001 |

The last row is not a phase. Setting up the support mailbox exposed two problems
in outgoing mail — Laravel's `Example` / `hello@example.com` From defaults reaching
real recipients, and Laravel's own logo one env value away from being served as
ours — so the mail views were published and branded, covered by
`tests/Feature/MailBrandingTest.php`. That work also turned up a 500 in the Phase 7
report endpoint when `reporter_email` was omitted from the request rather than sent
empty; because `AbuseReported` is queued, it would have lost the report to a retry
log instead of erroring where anyone would see it.

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
- [x] `support@easy-qr-code.com` receives mail. The domain had **no MX record at
      all**, so senders fell back to the A record — Cloudflare's proxy IPs, which
      do not answer on port 25 — and every message deferred for ~72h and then
      bounced. Fixed with Cloudflare Email Routing forwarding to a private inbox,
      plus a catch-all so `abuse@` and `privacy@` do not bounce either. Replies go
      out as support@ through a Gmail "Send mail as" alias on Resend's SMTP.
      Tested end to end 14 August 2026.
- [ ] `SITE_SUPPORT_EMAIL=support@easy-qr-code.com` set in production
- [ ] `MAIL_FROM_ADDRESS=support@easy-qr-code.com` and
      `MAIL_FROM_NAME="Easy QR Code"` set in production. Leaving either unset is
      not cosmetic: `config/mail.php` now falls back to our own domain and to
      `APP_NAME`, but before that fix mail went out signed **Example** from
      **hello@example.com** — a domain we do not own, which Resend rejects.
      `APP_NAME` is currently `EasyQR`, so without `MAIL_FROM_NAME` the sender
      reads "EasyQR" while the legal pages say Easy QR Code.
- [ ] `APP_URL=https://easy-qr-code.com`. This is load-bearing for email now: the
      logo in the mail header is built with `asset()`, so a wrong `APP_URL` means
      a broken image in every message the app sends.
- [ ] `SITE_COMPANY_NAME` / `SITE_COMPANY_ADDRESS` removed from `.env` — they are
      ignored after the Phase 8 rename, and leaving them there implies the site
      reads them
- [ ] The operator defaults now name **Mayst Impact DOOEL**, tax number
      **4032020546119**, so `SITE_OPERATOR_*` need not be set at all. Check the
      name and address character-for-character against the central registry entry
      instead: this is the string a reviewer compares to the AgentaOS account
      holder, and the address currently on the default is the one inherited from
      the old `SITE_COMPANY_ADDRESS`, which was never verified against the
      registry.
- [ ] `SITE_CREDIT_URL` removed from `.env` — the footer credit is off for launch
- [ ] Migrations ran — `qr_code_scans` has no `ip_address` and no `city`
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
- [ ] The Terms preserve EU/UK consumer rights (§11)
- [ ] No "Powered by" credit in the footer
- [ ] The support address is `support@easy-qr-code.com`, not a Gmail address
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
