# Phase 10 — Pre-renewal notice

**Goal:** a subscriber is told before their card is charged again, not after.

**Blocks submission:** no. This is the one phase that is a genuine new feature
rather than a correction, and it can ship after approval.

---

## Why

The subscription renews automatically every year
(`terms-and-conditions.blade.php:69-72`). A yearly cycle is long enough that a
customer will have forgotten they subscribed by the time the charge lands. That
is the classic pattern behind subscription chargebacks — and chargebacks are the
merchant of record's problem, which makes it AgentaOS's problem, which makes it a
thing they care about in an underwriting decision.

Two bodies of law point the same way:

- **EU Omnibus / consumer rights** — pressure toward clear pre-renewal
  information for automatically renewing contracts
- **California's Automatic Renewal Law** — requires advance notice before a
  renewal for subscriptions above a threshold with a term of a year or more

Neither is a hard blocker for a $27/year product operated from North Macedonia,
and neither is on the AgentaOS form. But a chargeback we prevented is worth more
than a clause we argued about, and "we email you before we charge you" is a good
line to have in the Terms.

### What exists today

`app/Notifications/` holds four notifications:

| Notification | When |
|---|---|
| `TrialEndingSoon` | T-2 days before the trial ends |
| `TrialEnded` | Trial expired without payment |
| `RenewalPaymentFailed` | A renewal charge failed |
| `BillingAlert` | Operational alert to us, not the customer |

There is **no pre-renewal notice**. We tell customers when a payment *failed*.
We never tell them one is coming.

## New

### `app/Notifications/SubscriptionRenewingSoon.php`

Same shape as the existing four: `extends Notification implements ShouldQueue`,
`use Queueable`, `via()` returns `['mail']`, `toMail(User $notifiable): MailMessage`.

Content — everything the customer needs to act, or to be reassured:

- The renewal date
- The amount, via `SubscriptionPrice::perInterval()` from
  [Phase 1](./phase-01-public-pricing.md)
- That it is charged automatically to the card on file
- That AgentaOS is the merchant of record and the charge appears under their name
  on the statement. This single line prevents a real fraction of "I don't
  recognise this charge" disputes.
- A direct link to the Subscription page to cancel
- That cancelling keeps access until the end of the paid period

Tone: a courtesy reminder, not a warning. Nothing is wrong.

### Sending

Extend `app/Console/Commands/SendBillingNotifications.php` (`billing:notify`,
scheduled daily at 09:00 in `routes/console.php`) rather than adding a command.
It is already the daily customer-email pass and already handles the trial notices.

Send when `current_period_end` is 7 days out, for subscriptions that are live and
**not** `cancel_at_period_end` — a customer who has already cancelled should not
get a renewal reminder.

> ⚠️ **This must not double-send.** The command runs daily; the condition must be
> a single day, not "within 7 days", or a subscriber gets seven identical emails.
> Follow whatever guard the trial notices already use for exactly-once sending —
> read `SendBillingNotifications.php` and match it rather than inventing a second
> mechanism. If it relies on a date equality check, be careful that
> `current_period_end` is reconciled from AgentaOS daily
> (`SyncAgentaOsSubscriptions`) and can move, which can make a pure date-equality
> guard skip or repeat. A persisted `renewal_notice_sent_at` on `subscriptions`
> is the robust answer if the existing guard does not generalise.

### Terms

Once this ships, `terms-and-conditions.blade.php:69-72` can say so:

> We will email you about seven days before each renewal, telling you the date and
> the amount, so you can cancel first if you no longer want it.

Add it **only** after the notification is live. Phase 8 deliberately leaves this
out for that reason.

## Tests

New tests in `tests/Feature/Subscription/BillingNotificationsTest.php`, beside the
existing trial-notice tests:

- `test_a_renewal_notice_goes_out_seven_days_before_the_period_ends`
- `test_a_renewal_notice_is_not_sent_to_a_cancelled_subscription`
- `test_a_renewal_notice_is_sent_only_once` — run `billing:notify` on several
  consecutive simulated days across the window and assert exactly one
  notification. This is the test that matters; the failure mode is spamming a
  paying customer.
- `test_a_renewal_notice_states_the_amount_and_the_renewal_date`
- `test_a_renewal_notice_names_agentaos_as_the_merchant_of_record`
- `test_a_trialing_user_gets_no_renewal_notice` — trials are ours, AgentaOS never
  knows about them, and there is nothing to renew

## Done when

- [ ] A subscriber 7 days from renewal receives exactly one notice
- [ ] Cancelled and trialing users receive none
- [ ] The email states date, amount, and that AgentaOS appears on the statement
- [ ] The Terms describe the notice
- [ ] `php artisan test --compact --filter=BillingNotificationsTest` green
