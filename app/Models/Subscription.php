<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'dynamic_qr_limit',
        'scans_per_code',
        'current_price',
        'next_billing_date',
    ];

    protected $casts = [
        'next_billing_date' => 'datetime',
        'dynamic_qr_limit' => 'integer',
        'scans_per_code' => 'integer',
        'current_price' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function incrementDynamicQrLimit(int $amount = 5): void
    {
        $this->update([
            'dynamic_qr_limit' => $this->dynamic_qr_limit + $amount,
            'current_price' => $this->current_price + 5.00,
        ]);
    }

    public function incrementScansPerCode(int $amount = 1000): void
    {
        $this->update([
            'scans_per_code' => $this->scans_per_code + $amount,
            'current_price' => $this->current_price + 5.00,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
