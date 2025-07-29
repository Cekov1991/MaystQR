<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class QrCode extends Model
{
    protected $fillable = [
        'name',
        'type',
        'content',
        'short_url',
        'destination_url',
        'options',
        'qr_code_image',
        'user_id',
        'expires_at',
    ];

    protected $casts = [
        'options' => 'array',
        'scan_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($qrCode) {
            if (!$qrCode->short_url) {
                $qrCode->short_url = static::generateUniqueShortUrl();
            }

            if (!$qrCode->user_id) {
                $qrCode->user_id = auth()->id();
            }

            if (!$qrCode->type) {
                $qrCode->type = 'static';
            }

            // Set 24-hour trial period for dynamic QR codes
            if ($qrCode->type === 'dynamic' && !$qrCode->expires_at) {
                $qrCode->expires_at = now()->addHours(24);
            }

            // Generate QR code image
            $qrCode->generateQrCode();
        });

        static::updating(function ($qrCode) {
            if ($qrCode->type === 'static' && $qrCode->isDirty('destination_url')) {
                throw new \Exception('Cannot update destination URL for static QR codes.');
            }

            // Regenerate QR code if content or options changed
            if ($qrCode->isDirty(['content', 'options'])) {
                $qrCode->generateQrCode();
            }
        });
    }

    protected function generateQrCode(): void
    {
        $options = $this->options ?? [];
        $format = $options['format'] ?? 'png';
        $size = $options['size'] ?? 300;
        $color = $options['color'] ?? '#000000';
        $errorCorrection = $options['errorCorrection'] ?? 'M';

        // Convert hex color to RGB
        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");

        $qrCode = QrCodeGenerator::format($format)
            ->size($size)
            ->errorCorrection($errorCorrection)
            ->color($r, $g, $b)
            ->generate($this->content);

        // Generate unique filename
        $filename = 'qr-codes/' . uniqid() . '.' . $format;

        // Store the QR code
        Storage::put($filename, $qrCode);

        // Delete old image if exists
        if ($this->qr_code_image) {
            Storage::delete($this->qr_code_image);
        }

        $this->qr_code_image = $filename;
    }

    protected static function generateUniqueShortUrl(int $length = 8): string
    {
        $characters = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $maxAttempts = 10;
        $attempt = 0;

        do {
            if ($attempt >= $maxAttempts) {
                $length++;
                $attempt = 0;
            }

            $shortUrl = '';
            $charactersLength = strlen($characters);

            for ($i = 0; $i < $length; $i++) {
                $shortUrl .= $characters[random_int(0, $charactersLength - 1)];
            }

            $attempt++;
        } while (static::where('short_url', $shortUrl)->exists());

        return $shortUrl;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scans()
    {
        return $this->hasMany(QrCodeScan::class);
    }

    public function packagePurchases()
    {
        return $this->hasMany(QrCodePackagePurchase::class);
    }

    // Expiration methods
    public function isExpired(): bool
    {
        if ($this->type === 'static') {
            return false; // Static QR codes never expire
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    public function isInTrial(): bool
    {
        if ($this->type === 'static') {
            return false;
        }

        // Check if this QR code has never been extended (no successful package purchases)
        return !$this->packagePurchases()->where('status', 'completed')->exists();
    }

    public function getTimeUntilExpiry(): ?Carbon
    {
        if ($this->type === 'static' || !$this->expires_at) {
            return null;
        }

        return $this->expires_at->isPast() ? null : $this->expires_at;
    }

    public function extendValidity(int $months): void
    {
        $newExpirationDate = $this->isExpired()
            ? now()->addMonths($months)
            : $this->expires_at->addMonths($months);

        $this->update(['expires_at' => $newExpirationDate]);
    }

    public function canBeScanned(): bool
    {
        // Check if QR code is expired (for dynamic QR codes)
        if ($this->isExpired()) {
            return false;
        }

        return true;
    }

    // Scope for filtering expired QR codes
    public function scopeExpired($query)
    {
        return $query->where('type', 'dynamic')
                    ->where('expires_at', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('type', 'static')
              ->orWhere(function ($q2) {
                  $q2->where('type', 'dynamic')
                     ->where(function ($q3) {
                         $q3->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                     });
              });
        });
    }
}
