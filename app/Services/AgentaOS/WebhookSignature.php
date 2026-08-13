<?php

namespace App\Services\AgentaOS;

/**
 * Verifies the `X-AgentaOS-Signature: t=<unix>,v1=<hex>` header.
 *
 * `v1` is an HMAC-SHA256 of "{t}.{raw body}" keyed with the webhook signing
 * secret. The timestamp is part of the signed material so the same payload
 * never signs to the same value twice and a captured request cannot be
 * replayed indefinitely.
 */
class WebhookSignature
{
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * @param  string  $payload  The raw request body, byte for byte. A re-encoded
     *                           array will not match the signature.
     */
    public static function isValid(
        string $payload,
        ?string $signature,
        ?string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): bool {
        if ($signature === null || $secret === null || $secret === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signature) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            $parts[trim((string) $key)] = $value;
        }

        $timestamp = (int) ($parts['t'] ?? 0);
        $digest = $parts['v1'] ?? null;

        if ($timestamp === 0 || $digest === null || $digest === '') {
            return false;
        }

        // Replay protection: also rejects timestamps from the future, which a
        // clock-skewed forgery would need in order to stay valid.
        if (abs(time() - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        // Constant-time: a naive comparison leaks enough timing information to
        // forge a signature byte by byte.
        return hash_equals($expected, $digest);
    }
}
