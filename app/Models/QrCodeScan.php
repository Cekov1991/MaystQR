<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCodeScan extends Model
{
    public function qrCode()
    {
        return $this->belongsTo(QrCode::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scanned_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scanned_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('scanned_at', now()->month)
            ->whereYear('scanned_at', now()->year);
    }
}
