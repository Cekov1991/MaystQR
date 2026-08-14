# AgentaOS Go-Live — Compliance Readiness Plan

Everything that must be true on `easy-qr-code.com` before the production application
is submitted at `app.agentaos.ai/go-live`.

**Status:** Phases 1–2 implemented and tested. Phases 3–10 planned.
**Applicant:** Stefan Cekov, as an individual (not a company).
**Related:** [subscription plan](../subscription-implementation-plan.md) · [ADR-0001](../adr/0001-account-level-entitlement-replaces-per-code-expiry.md) · [ADR-0002](../adr/0002-entitlement-is-a-local-date-reconciled-from-agentaos.md)

---

## Why this exists

AgentaOS is our Merchant of Record: they sell on our behalf, so a human reviewer
opens the site and verifies that what it says matches what it does. The go-live
form asks us to attest to five things. Two of those attestations were **not true**
when this plan was written, and one public page actively contradicted our pricing.

This is not a legal-polish exercise. Every phase below closes a gap between a
claim the site makes and the way the application actually behaves.

## The attestations we are signing

| Form question | Our answer | True today? | Closed by |
|---|---|---|---|
| Usage claims or reviews displayed? | No, none | ✅ Now genuinely true — badge deleted, not commented | [Phase 2](./phase-02-remove-parked-landing.md) ✅ |
| Existing paying customers? | No, just starting out | ✅ Yes | — |
| Product name infringes a trademark? | No | ✅ Yes | — |
| Pricing accessible and clear before purchase? | Yes | ✅ Now true — public `/pricing` page | [Phase 1](./phase-01-public-pricing.md) ✅ |
| Publicly accessible Privacy Policy? | Yes | ⚠️ Reachable, but describes tracking we do not run | [Phases 3–6](./phase-03-remove-google.md) |
| Publicly accessible Terms of Service? | Yes | ✅ Reachable | [Phase 8](./phase-08-legal-identity.md) refines it |
| High-risk / shady use of technology? | No | ✅ Yes — [Phase 7](./phase-07-abuse-reporting.md) proves it |

Answers one and two are honest and must **stay** honest. Do not add a testimonial,
a user counter or a "trusted by" logo strip before launch. The moment the site
carries social proof we cannot substantiate, the first attestation becomes false.

## Phases

| # | Phase | Status | Blocks submission? | Kind |
|---|---|---|---|---|
| 1 | [Public pricing](./phase-01-public-pricing.md) | ✅ Done | **Yes** | Feature + copy |
| 2 | [Remove the parked landing page](./phase-02-remove-parked-landing.md) | ✅ Done | **Yes** | Deletion |
| 3 | [Remove Google from the public site](./phase-03-remove-google.md) | Planned | **Yes** | Copy + asset |
| 4 | [Privacy policy rewrite](./phase-04-privacy-policy.md) | Planned | **Yes** | Copy |
| 5 | [Stop storing scanner IPs](./phase-05-remove-scanner-ip.md) | Planned | No, but do it | Migration |
| 6 | [Enforce scan retention](./phase-06-scan-retention.md) | Planned | No — makes Phase 4 true | Feature |
| 7 | [Abuse reporting](./phase-07-abuse-reporting.md) | Planned | No — strongest positive signal | Feature |
| 8 | [Legal identity and Terms](./phase-08-legal-identity.md) | Planned | **Yes** | Config + copy |
| 9 | [Verification](./phase-09-verification.md) | Planned | **Yes** | Checklist |
| 10 | [Pre-renewal notice](./phase-10-renewal-notice.md) | Planned | No | Feature |

Phases 1–4 and 8 are the submission gate. 5–7 and 10 make the application
stronger and close real legal exposure, but the form can be submitted without
them — **except** that Phase 4 states a scan retention period, and Phase 6 is
what makes that statement true. Ship 4 and 6 together or soften the wording in 4.

## Ordering

```
1 ──┐
2 ──┤
3 ──┼──► 9 (verify) ──► submit
8 ──┤
5 ──► 6 ──► 4 ──┘
        7 ──┘
```

Phases 1, 2, 3 and 8 are independent of each other and can be done in any order.
Phase 4's copy depends on Phase 5 (it states that no IP is stored) and Phase 6
(it states a retention window), so 4 lands last of that group. Phase 7 adds a
footer link and a Terms clause, so it should land before Phase 9's final read-through.

[Phase 10](./phase-10-renewal-notice.md) is deliberately outside the diagram. It
is the only phase that builds something new rather than correcting something
false, and it can ship after approval. Phase 8 is written to avoid promising the
renewal notice in the Terms until Phase 10 makes it real.

## Facts this plan is built on

Confirmed by reading the code, not assumed:

| Fact | Source |
|---|---|
| Hosting is Laravel Cloud, **United States** | Confirmed by owner; contradicts `privacy-policy.blade.php:126` |
| CDN, TLS and scan country come from Cloudflare | `QrCodeRedirectController.php:106-115` |
| Transactional email goes through Resend | Confirmed by owner |
| Payments and invoicing are AgentaOS as MoR | `terms-and-conditions.blade.php:73` |
| Google Analytics is **not installed** anywhere | No `gtag`/GTM tag in any view |
| Full scanner IPs are stored and shown to customers | `QrCodeRedirectController.php:87`, `ScansRelationManager.php:45` |
| Price is $27/year, tax inclusive | `config/subscription.php` |
| Trial is 7 days, no card required | `config/subscription.php` |
| There is no pre-renewal reminder email | `app/Notifications/` holds four, none of them this |

## Deploy-time actions

Things no phase can do for you, gathered here so none is forgotten:

- [ ] Set `SITE_SUPPORT_EMAIL=support@easy-qr-code.com` and make sure the mailbox
      receives mail (Phase 8 changes the default, but production `.env` wins).
- [ ] Set `SITE_OPERATOR_NAME` / `SITE_OPERATOR_ADDRESS`, or delete the old
      `SITE_COMPANY_*` vars so the new defaults apply (Phase 8).
- [ ] Decide on the `SITE_CREDIT_URL` footer credit (Phase 8).
- [ ] Confirm the cron entry `* * * * * php artisan schedule:run` exists on
      Laravel Cloud — without it, Phase 6's pruning never runs and Phase 4's
      retention claim silently becomes false again.
- [ ] Run `npm run build` if any Filament-side styling changed.
