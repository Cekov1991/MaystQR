<?php

namespace App\Traits;

trait HasDynamicQrCodePricing
{
    public function calculateDynamicQrCodePrice(): float
    {
        $currentCount = $this->qrCodes()->where('type', 'dynamic')->count();
        return $currentCount >= 1 ? 5.00 : 0.00; // First one is free, then $5 each
    }

    public function getDynamicQrCodePriceWarning(): ?string
    {
        $price = $this->calculateDynamicQrCodePrice();
        if ($price > 0) {
            return "Creating this dynamic QR code will increase your monthly subscription by \${$price}.";
        }
        return null;
    }
}
