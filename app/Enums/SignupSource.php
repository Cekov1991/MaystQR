<?php

namespace App\Enums;

/**
 * Where an account came from, as one durable fact on the user's own row.
 *
 * This is the deliberate exception to the rule that governs TrackedEvent. That
 * table counts occurrences and is forbidden an identifier, which means it can
 * say how many people clicked the offer but never whether any of them went on to
 * register. The question is worth answering, so it is answered here instead: on
 * the account itself, as a single label set once at registration, rather than by
 * stitching a stream of anonymous rows back together into a profile.
 *
 * A closed set rather than a string column is the whole point. `?ref=` arrives
 * from a URL a stranger can type anything into, and an unvalidated column would
 * become a free-text sink filled by whoever felt like filling it — the same
 * unbounded-cardinality failure the event context guards against. Anything not
 * named here resolves to null, which reads correctly as "we do not know".
 */
enum SignupSource: string
{
    /**
     * The offer shown in the result panel after someone downloads a free static
     * code. Phase 5 adds the quiet inline link beside it as a second arm, and the
     * comparison between the two is the reason this is a set and not a boolean.
     */
    case StaticOffer = 'static-offer';

    /**
     * Resolve a `?ref=` value, or null if it is not one we published.
     *
     * Null is not a failure and is never reported as one: most registrations
     * arrive with no ref at all, and a typed or stale link is indistinguishable
     * from that. Attribution is a nice-to-have; it must never be a reason a
     * registration does not complete.
     */
    public static function fromRef(?string $ref): ?self
    {
        return $ref === null ? null : self::tryFrom($ref);
    }
}
