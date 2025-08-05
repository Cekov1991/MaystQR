<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCodePackage extends Model
{
    protected $fillable = [
        'paddle_price_id',
        'name',
        'duration_months',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_months' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function purchases()
    {
        return $this->hasMany(QrCodePackagePurchase::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getFormattedPriceAttribute(): string
    {
        return '€' . number_format($this->price, 2);
    }

    public function getDurationTextAttribute(): string
    {
        return $this->duration_months === 1
            ? '1 Month'
            : $this->duration_months . ' Months';
    }
}