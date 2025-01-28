<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'dynamic_qr_limit',
        'scans_per_code',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'dynamic_qr_limit' => 'integer',
        'scans_per_code' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
