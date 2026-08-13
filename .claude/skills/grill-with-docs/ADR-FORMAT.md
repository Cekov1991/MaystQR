# ADR Format

ADRs live in `docs/adr/` and use sequential numbering: `0001-slug.md`, `0002-slug.md`, etc.

Create the `docs/adr/` directory lazily — only when the first ADR is needed.

## Template

```md
# {Short title of the decision}

{1-3 sentences: what's the context, what did we decide, and why.}
```

That's it. An ADR can be a single paragraph. The value is in recording *that* a decision was made and *why* — not in filling out sections.

## Optional sections

Only include these when they add genuine value. Most ADRs won't need them.

- **Status** frontmatter (`proposed | accepted | deprecated | superseded by ADR-NNNN`) — useful when decisions are revisited
- **Considered Options** — only when the rejected alternatives are worth remembering
- **Consequences** — only when non-obvious downstream effects need to be called out

## Numbering

Scan `docs/adr/` for the highest existing number and increment by one.

## When to offer an ADR

All three of these must be true:

1. **Hard to reverse** — the cost of changing your mind later is meaningful
2. **Surprising without context** — a future reader will look at the code and wonder "why on earth did they do it this way?"
3. **The result of a real trade-off** — there were genuine alternatives and you picked one for specific reasons

If a decision is easy to reverse, skip it — you'll just reverse it. If it's not surprising, nobody will wonder why. If there was no real alternative, there's nothing to record beyond "we did the obvious thing."

### What qualifies

- **Architectural shape.** "We use a monorepo with pnpm workspaces." "The analytics pipeline runs through esim-api (NestJS + Pub/Sub → Postgres), not by querying Firestore directly from Metabase."
- **Integration patterns between apps.** "The backend writes Booking state to Firestore; esim-api subscribes to Pub/Sub change events and mirrors them to Postgres — they do not call each other directly."
- **Technology choices that carry lock-in.** Firebase, Cloudflare Workers (metabase-worker), Telna/Bondio provider contracts, Brevo for email, Postgres for analytics. Not every library — just the ones that would take a quarter to swap out.
- **Boundary and scope decisions.** "Analytics reads happen against Postgres, never against Firestore directly — Metabase is wired to Postgres only." The explicit no-s are as valuable as the yes-s.
- **Deliberate deviations from the obvious path.** "We use a spec+builder pattern instead of raw Zod schemas because the same domain model must compile to both Firebase Admin types and client-safe types." Anything where a reasonable reader would assume the opposite.
- **Constraints not visible in the code.** "Telna requires IMSI-level provisioning before a Package can be activated." "Partner eSIM assignment must complete within the booking confirmation webhook timeout."
- **Rejected alternatives when the rejection is non-obvious.** If you considered a REST API between backend and esim-api but kept Firestore as the shared state layer for a specific reason, record it — otherwise someone will add HTTP calls between them.
