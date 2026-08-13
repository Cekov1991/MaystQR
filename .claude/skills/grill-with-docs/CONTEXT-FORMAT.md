# CONTEXT.md Format

## Structure

```md
# Hubby eSIM Platform

A B2B2C platform that sells eSIM data packages to travellers through partner companies.

## Language

**Booking**:
A travel booking submitted by a Partner on behalf of a traveller, containing departure/return dates and one or more PackageSpecifications.
_Avoid_: order, reservation, trip

**EsimPackage**:
A data package activated on a specific eSIM, living in the `esims/{iccid}/packages` Firestore subcollection.
_Avoid_: package (alone — qualify which: EsimPackage, Package, or PackageSpecification)

**Partner**:
A B2B company that distributes eSIMs to travellers through Hubby's platform.
_Avoid_: customer, client, reseller (as a generic term — it's a specific partner type)

## Relationships

- A **Partner** submits many **Bookings** on behalf of **Users**
- A **Booking** is linked to one or more **eSIMs** after confirmation
- An **eSIM** carries one or more **EsimPackages**

## Example dialogue

> **Dev:** "When a **Partner** submits a **Booking**, do we immediately assign an **eSIM**?"
> **Domain expert:** "No — the Booking starts as `PENDING`. Once confirmed, the backend picks an available eSIM and creates **EsimPackages** on it based on the **PackageSpecifications**."

## Flagged ambiguities

- "package" is overloaded — resolved: **Package** (catalog item), **EsimPackage** (activated on an eSIM), **PackageSpecification** (request inside a Booking).
- "customer" was used for both **Partner** and **User** — resolved: Partner is the B2B company, User is the traveller holding the eSIM.
```

## Rules

- **Be opinionated.** When multiple words exist for the same concept, pick the best one and list the others as aliases to avoid.
- **Flag conflicts explicitly.** If a term is used ambiguously, call it out in "Flagged ambiguities" with a clear resolution.
- **Keep definitions tight.** One sentence max. Define what it IS, not what it does.
- **Show relationships.** Use bold term names and express cardinality where obvious.
- **Only include terms specific to this project's context.** General programming concepts (timeouts, error types, utility patterns) don't belong even if the project uses them extensively. Before adding a term, ask: is this a concept unique to this context, or a general programming concept? Only the former belongs.
- **Group terms under subheadings** when natural clusters emerge. If all terms belong to a single cohesive area, a flat list is fine.
- **Write an example dialogue.** A conversation between a dev and a domain expert that demonstrates how the terms interact naturally and clarifies boundaries between related concepts.

## Single vs multi-context repos

**Single context (most repos):** One `CONTEXT.md` at the repo root.

**Multiple contexts:** A `CONTEXT-MAP.md` at the repo root lists the contexts, where they live, and how they relate to each other:

```md
# Context Map

## Contexts

- [Backend](./apps/backend/CONTEXT.md) — Firebase Functions: booking flow, eSIM assignment, messaging
- [eSIM API](./apps/esim-api/CONTEXT.md) — NestJS: subscribes to Firestore Pub/Sub, syncs to Postgres for analytics
- [Admin Panel](./apps/adminpanel/CONTEXT.md) — SvelteKit: internal tooling for Hubby staff
- [Webapp](./apps/webapp/CONTEXT.md) — SvelteKit: consumer-facing B2C storefront
- [Metabase Worker](./apps/metabase-worker/CONTEXT.md) — Cloudflare Worker: signed Metabase URLs for partners

## Relationships

- **Backend → esim-api**: Backend writes Bookings, eSIMs, and EsimPackages to Firestore; esim-api subscribes to Pub/Sub change events and mirrors them to Postgres
- **Backend → Providers**: Backend calls Telna/Bondio to provision eSIMs and activate EsimPackages
- **Shared types**: `@hubbyesim/types` defines Zod schemas consumed by all apps
```

The skill infers which structure applies:

- If `CONTEXT-MAP.md` exists, read it to find contexts
- If only a root `CONTEXT.md` exists, single context
- If neither exists, create a root `CONTEXT.md` lazily when the first term is resolved

When multiple contexts exist, infer which one the current topic relates to. If unclear, ask.
