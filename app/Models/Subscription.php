<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'paypal_subscription_id',
        'status',
        'dynamic_qr_codes_limit',
        'monthly_scan_limit',
        'has_advanced_analytics',
        'has_custom_branding',
        'current_price',
        'next_billing_date',
    ];

    protected $casts = [
        'has_advanced_analytics' => 'boolean',
        'has_custom_branding' => 'boolean',
        'current_price' => 'decimal:2',
        'next_billing_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function features()
    {
        return $this->hasMany(SubscriptionFeature::class);
    }

    public function addFeature(string $featureKey, int|bool $value, float $price): SubscriptionFeature
    {
        $feature = new SubscriptionFeature([
            'feature_key' => $featureKey,
            'price' => $price,
            'added_at' => now(),
        ]);

        if (is_bool($value)) {
            $feature->enabled = $value;
        } else {
            $feature->quantity = $value;
        }

        $this->features()->save($feature);
        $this->updateTotalPrice();

        return $feature;
    }

    public function updateTotalPrice(): void
    {
        $this->current_price = $this->features()->sum('price');
        $this->save();
    }
}
