<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Illuminate\Support\Facades\Storage;

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
        'folder_id',
        'user_id',
    ];

    protected $casts = [
        'options' => 'array',
        'scan_count' => 'integer',
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
        Storage::disk('public')->put($filename, $qrCode);

        // Delete old image if exists
        if ($this->qr_code_image) {
            Storage::disk('public')->delete($this->qr_code_image);
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

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function scans()
    {
        return $this->hasMany(QrCodeScan::class);
    }

    public function userAddons()
    {
        return $this->hasMany(UserAddon::class);
    }

    public function canBeScanned(): bool
    {
        $user = $this->user;
        $monthlyScans = $this->scans()
            ->whereMonth('scanned_at', now()->month)
            ->whereYear('scanned_at', now()->year)
            ->count();

        return $monthlyScans < $user->getRemainingScans($this);
    }

    public function hasCustomization(): bool
    {
        return $this->user->hasCustomBranding();
    }

    public function getAvailableAnalytics(): array
    {
        $analytics = [
            'basic' => [
                'total_scans',
                'scans_by_date',
            ]
        ];

        if ($this->user->hasAdvancedAnalytics()) {
            $analytics['advanced'] = [
                'user_demographics',
                'geofencing',
                'engagement_trends',
                'custom_reports'
            ];
        }

        return $analytics;
    }
}
