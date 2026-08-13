<?php

namespace App\Enums;

enum AccountState: string
{
    case Trialing = 'trialing';
    case Subscribed = 'subscribed';
    case Lapsed = 'lapsed';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Free trial',
            self::Subscribed => 'Subscribed',
            self::Lapsed => 'Inactive',
        };
    }

    /**
     * Whether dynamic QR codes owned by an account in this state resolve when scanned.
     */
    public function resolvesDynamicCodes(): bool
    {
        return $this !== self::Lapsed;
    }
}
