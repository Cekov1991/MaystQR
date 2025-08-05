<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCodePackagePurchase extends Model
{
    protected $fillable = [
        'qr_code_id',
        'qr_code_package_id',
        'user_id',
        'amount_paid',
        'purchased_at',
        'extended_until',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'purchased_at' => 'datetime',
        'extended_until' => 'datetime',
    ];

    // Relationships
    public function qrCode()
    {
        return $this->belongsTo(QrCode::class);
    }

    public function package()
    {
        return $this->belongsTo(QrCodePackage::class, 'qr_code_package_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helper methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'purchased_at' => now(),
        ]);

        // Extend the QR code validity
        $this->qrCode->update([
            'expires_at' => $this->extended_until
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}