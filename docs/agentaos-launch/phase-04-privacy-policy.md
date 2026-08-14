# Phase 4 — Privacy policy rewrite

**Goal:** the privacy policy describes the system we actually run, names every
company that touches personal data, and states the correct country.

**Blocks submission:** yes.

**Depends on:** [Phase 3](./phase-03-remove-google.md) (Google claims removed),
[Phase 5](./phase-05-remove-scanner-ip.md) (no IP stored — §Scan data asserts this),
[Phase 6](./phase-06-scan-retention.md) (retention window enforced — §Retention asserts this).

---

## Why

Beyond the Google problem Phase 3 removes, the current document has one
outright factual error and several standard omissions a reviewer's checklist
will look for.

### The factual error

`privacy-policy.blade.php:124-128`:

> *"If you access our Service from outside The Republic of North Macedonia, your
> data will be stored and processed in The Republic of North Macedonia, where
> data protection laws may differ."*

We host on **Laravel Cloud, in the United States**. The stated country is wrong,
which means the one paragraph in the document about international transfers is
telling every visitor the opposite of what happens.

This matters more than a typo. North Macedonia and the United States are both
outside the EEA, and neither is covered by an adequacy decision that applies to
us. So the paragraph needs to name the real country *and* the legal mechanism
that makes the transfer lawful — right now it names neither.

### The omissions

| Missing | Why a reviewer wants it |
|---|---|
| Controller identity | Who is legally answerable. §12 gives an email and address but never says "the controller is X" |
| Legal bases | §4 lists purposes but never says which lawful basis covers each |
| Named processors | §7 says *"service providers (e.g., hosting, payment processors, analytics)"* — generic. AgentaOS is named in the Terms and the Refund Policy but not here |
| Transfer mechanism | See above |
| Retention periods | §6 says *"as long as your account is active"* with no number for anything |
| Scan data | The policy never explains what is recorded about people who **scan** a code — third parties who never visited the site |
| Supervisory authority | The GDPR right to complain is absent from the §8 rights list |
| Children | Terms §3 sets 18+; the privacy policy says nothing |

## The scan-data gap is the substantive one

Everything else here is documentation. This one is a real exposure.

`QrCodeRedirectController.php:75-96` writes a row for every scan holding IP,
user agent, referer, device, OS, browser and country. The people in those rows
are **strangers**: someone who scanned a poster. They never visited
`easy-qr-code.com`, never saw this policy, and have no account.

The policy currently mentions *"Basic QR code scan statistics (for dynamic QR
codes)"* in a bullet under "information collected automatically", framed as
though it were about the logged-in user. It is not.

Phase 5 removes the IP, which is the sharpest part. Phase 4 must then explain
plainly what is left, who sees it, and for how long.

## Edit

`resources/views/privacy-policy.blade.php` — restructure to:

```
Who we are (controller identity)
1. Introduction
2. Information we collect
   a) Information you provide
   b) Information collected automatically
   c) Cookies                          ← rewritten in Phase 3
3. QR code scan data                   ← new
4. Why we use your information, and our legal basis   ← basis column is new
5. Who processes your data             ← was §7, now named
6. Where your data is stored           ← was §10, was wrong
7. How long we keep it                 ← was §6, now has numbers
8. Email communication
9. Your rights                         ← + supervisory authority
10. Data security
11. Children
12. Changes to this policy
13. Contact us
```

### Who we are

Uses the Phase 8 config keys so this cannot drift from the Terms:

> **Data controller:** Stefan Cekov
> **Address:** {{ config('site.operator.address') }}
> **Email:** {{ config('site.support_email') }}
>
> Stefan Cekov operates {{ config('site.domain') }} as an individual and is the
> data controller for the personal data described in this policy.

### §3 QR code scan data — new, verbatim

> When someone scans a dynamic QR code, our servers record that the scan
> happened so the person who created the code can see how it is performing.
> We record the date and time, the approximate country (supplied by Cloudflare
> from the network connection), and the device type, operating system and
> browser reported by the scanning device. We also record the referring page
> where a browser supplies one.
>
> **We do not store the IP address of anyone who scans a QR code.**
>
> This information is visible to the person who created the code, in aggregate
> and as individual scan records. It is not linked to a name, an account or any
> identifier we could use to recognise the same person again.
>
> If you scanned a QR code and want to know what was recorded, contact us with
> the code's address and the approximate time and we will help — though because
> we hold nothing that identifies you, we will usually be unable to single out
> your individual scan.

That last paragraph is deliberate. It is honest about a real limit on the access
right instead of promising a lookup we cannot perform.

### §4 Legal basis

Replace the flat purpose list with a table:

| Purpose | Legal basis |
|---|---|
| Creating and managing your account | Performance of a contract |
| Generating, storing and resolving your QR codes | Performance of a contract |
| Taking payment and handling renewals | Performance of a contract |
| Service and security emails | Performance of a contract |
| Scan statistics for the code's owner | Legitimate interest — providing the feature the owner subscribed for |
| Rate limiting, abuse prevention, fraud checks | Legitimate interest — keeping the service available and lawful |
| Product news and promotions | Consent, withdrawable at any time |
| Keeping records required by tax law | Legal obligation |

### §5 Who processes your data

Render from a single config list rather than prose, so the page and reality stay
tied together. Add to `config/site.php`:

```php
'processors' => [
    ['name' => 'Laravel Cloud',  'role' => 'Application hosting and database',        'location' => 'United States'],
    ['name' => 'Cloudflare',     'role' => 'CDN, TLS, bot protection, scan country',  'location' => 'Global edge network'],
    ['name' => 'Resend',         'role' => 'Transactional email delivery',            'location' => 'United States'],
    ['name' => 'AgentaOS',       'role' => 'Payment processing as merchant of record', 'location' => 'See their privacy policy'],
],
```

Introduce it with:

> We use a small number of companies to run the service. Each processes personal
> data only on our instructions and only as needed for its role. **We do not
> sell your personal data, and we do not share it with advertisers.**

Then, on AgentaOS specifically:

> AgentaOS acts as the merchant of record for every purchase. When you subscribe,
> you buy from AgentaOS rather than from us, and they collect and hold your payment
> details, billing address and tax location directly — we never see or store your
> card details. They issue your invoice and receipt, and they are the controller
> for that payment data. We receive only a subscription reference, its status and
> its renewal date.

If Phase 3 kept a font CDN, add it. If Phase 3 self-hosted, do not.

### §6 Where your data is stored — verbatim

> Our application and database are hosted by Laravel Cloud on servers in the
> **United States**. Cloudflare operates a global edge network, so requests to
> our site may be routed through a Cloudflare location near you before reaching
> our servers. Email is delivered through Resend, also in the United States.
>
> If you are in the European Economic Area, the United Kingdom or Switzerland,
> this means your personal data is transferred outside your region. Where a
> transfer is not covered by an adequacy decision, we rely on the European
> Commission's Standard Contractual Clauses, which each of our processors has
> entered into, as the legal mechanism for that transfer.

> ⚠️ Before publishing, confirm that the Laravel Cloud, Cloudflare and Resend
> data processing agreements do incorporate the SCCs. All three publish a DPA
> that does, but check rather than assert — this paragraph is a legal
> representation, and an unverified one is worse than a vague one.

### §7 How long we keep it

| Data | Retention |
|---|---|
| Account details (name, email) | While your account is active. Erased within **30 days** of deletion, backups included |
| QR codes and their destinations | While your account is active; erased with the account |
| QR code scan records | **24 months**, then deleted automatically |
| Login session records | **2 weeks** (the session lifetime), then deleted |
| Subscription records | As long as required by tax and accounting law. AgentaOS holds the invoice as merchant of record |
| Server and security logs | **90 days** |

24 months on scans gives a subscriber year-over-year comparison while keeping
the window bounded and defensible.

> ⚠️ **Phase 6 is what makes the 24-month row true.** If Phase 6 does not ship,
> change this row to "while your account is active" rather than publishing a
> number nothing enforces. Publishing an unenforced retention period is the
> same defect as the Google Analytics claim.

The 2-week session row reflects `config/session.php` and the `sessions` table,
which stores an IP and user agent per session (`0001_01_01_000000_create_users_table.php:33`).
Disclose that in §2b — it is genuinely necessary for session security, so it
stays, but it should not be undisclosed.

### §9 Your rights

Keep the existing list, add:

> You also have the right to lodge a complaint with a data protection
> supervisory authority. If you are in the EEA or the UK, that is the authority
> in your country of residence. We would rather you contacted us first at
> {{ config('site.support_email') }} so we can put things right.

### §11 Children

> The service is not intended for children. You must be at least 18 years old
> to create an account, as set out in our Terms. We do not knowingly collect
> personal data from children. If you believe a child has given us personal
> data, contact us and we will delete it.

Mirrors Terms §3 so the two documents agree.

## Tests

In `tests/Feature/Subscription/PublicPagesTest.php`:

- `test_the_privacy_policy_names_the_data_controller` — sees "Stefan Cekov" and
  "data controller"
- `test_the_privacy_policy_names_every_processor` — loops
  `config('site.processors')` and asserts each name renders. Guards against
  adding a processor to config and forgetting the page, and vice versa.
- `test_the_privacy_policy_states_where_data_is_hosted` — sees "United States",
  and **does not** see "stored and processed in The Republic of North Macedonia"
- `test_the_privacy_policy_names_the_transfer_mechanism` — sees "Standard
  Contractual Clauses"
- `test_the_privacy_policy_states_no_scanner_ip_is_stored`
- `test_the_privacy_policy_states_a_scan_retention_period` — sees the configured
  window from Phase 6, not a hardcoded "24"
- `test_the_privacy_policy_lists_the_legal_bases` — sees "Performance of a
  contract", "Legitimate interest", "Consent"
- `test_the_privacy_policy_explains_the_right_to_complain_to_a_supervisory_authority`
- `test_the_privacy_policy_and_terms_agree_on_the_minimum_age` — both say 18

The processor test is the one worth writing carefully. It is the mechanism that
keeps this document honest after we stop thinking about it.

## Done when
- [ ] Every claim in the document is verifiable from the codebase or a signed DPA
- [ ] The stated hosting country is the United States
- [ ] All four processors are named, with roles
- [ ] Retention has numbers, and each number is enforced by something
- [ ] Scan data has its own section written for the person who scanned
- [ ] `Last updated` date bumped
- [ ] `php artisan test --compact --filter=PublicPagesTest` green
