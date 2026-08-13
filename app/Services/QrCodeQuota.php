<?php

namespace App\Services;

use App\Models\User;
use InvalidArgumentException;

/**
 * Decides whether a user may create another QR code of a given type.
 *
 * Quotas gate creation only. An account that is over its limit — because the
 * limit was lowered, or introduced after the codes were made — keeps every
 * existing code resolving. Printed assets are never broken by a rule added
 * after they were produced.
 */
class QrCodeQuota
{
    public function __construct(private readonly User $user) {}

    public function limitFor(string $type): int
    {
        return match ($type) {
            'dynamic' => $this->user->dynamic_qr_limit ?? (int) config('subscription.quotas.dynamic'),
            'static' => $this->user->static_qr_limit ?? (int) config('subscription.quotas.static'),
            default => throw new InvalidArgumentException("Unknown QR code type [{$type}]."),
        };
    }

    public function usedFor(string $type): int
    {
        return $this->user->qrCodes()->where('type', $type)->count();
    }

    public function remainingFor(string $type): int
    {
        return max(0, $this->limitFor($type) - $this->usedFor($type));
    }

    public function hasReachedLimitFor(string $type): bool
    {
        return $this->usedFor($type) >= $this->limitFor($type);
    }

    /**
     * Dynamic codes additionally require a live entitlement — a lapsed account
     * keeps its static allowance but cannot add codes that would not resolve.
     */
    public function canCreate(string $type): bool
    {
        if ($type === 'dynamic' && ! $this->user->isEntitled()) {
            return false;
        }

        return ! $this->hasReachedLimitFor($type);
    }
}
