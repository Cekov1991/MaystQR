<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class QrCode extends Model
{
    const QR_CONTENT_TYPES = [
        'website' => '🌐 Website',
        'wifi' => '📶 Wi-Fi Network',
        'email' => '📧 Email',
        'whatsapp' => '💬 WhatsApp',
        'vcard' => '👤 Contact (vCard)',
        'sms' => '💬 SMS',
        'phone' => '📞 Phone Call',
        'calendar' => '📅 Calendar Event',
        // 'text' => '📄 Plain Text',
        // 'location' => '📍 Location',
    ];

    protected $fillable = [
        'name',
        'type',
        'qr_content_type',
        'qr_content_data',
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
        'qr_content_data' => 'array',
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

            if (!$qrCode->qr_content_type) {
                $qrCode->qr_content_type = 'website';
            }

            // Generate content based on QR type
            $qrCode->content = $qrCode->generateContentFromType();

            // Set 24-hour trial period for dynamic QR codes
            if ($qrCode->type === 'dynamic' && !$qrCode->expires_at) {
                $qrCode->expires_at = now()->addHours(24);
                // For dynamic QRs, content should point to redirect route
                $qrCode->content = route('qr.redirect', $qrCode->short_url);
            }

            // Generate QR code image
            $qrCode->generateQrCode();
        });

        static::updating(function ($qrCode) {
            if ($qrCode->type === 'static' && $qrCode->isDirty('destination_url')) {
                throw new \Exception('Cannot update destination URL for static QR codes.');
            }

            // Regenerate content if qr_content_type or qr_content_data changed
            if ($qrCode->isDirty(['qr_content_type', 'qr_content_data']) && $qrCode->type === 'static') {
                $qrCode->content = $qrCode->generateContentFromType();
            }

            // Regenerate QR code if content or options changed
            if ($qrCode->isDirty(['content', 'options', 'qr_content_type', 'qr_content_data'])) {
                $qrCode->generateQrCode();
            }
        });
    }

    protected function generateContentFromType(): string
    {
        $data = $this->qr_content_data ?? [];

        return match ($this->qr_content_type) {
            'wifi' => $this->generateWifiContent($data),
            'email' => $this->generateEmailContent($data),
            'whatsapp' => $this->generateWhatsAppContent($data),
            'vcard' => $this->generateVCardContent($data),
            'sms' => $this->generateSmsContent($data),
            'phone' => $this->generatePhoneContent($data),
            'text' => $this->generateTextContent($data),
            'calendar' => $this->generateCalendarContent($data),
            'location' => $this->generateLocationContent($data),
            'website' => $this->generateWebsiteContent($data),
            default => $this->destination_url ?? '',
        };
    }

    protected function generateWifiContent(array $data): string
    {
        $security = $data['security'] ?? 'WPA2';
        $ssid = $data['ssid'] ?? '';
        $password = $data['password'] ?? '';
        $hidden = ($data['hidden'] ?? false) ? 'true' : 'false';

        return "WIFI:T:{$security};S:{$ssid};P:{$password};H:{$hidden};;";
    }

    protected function generateEmailContent(array $data): string
    {
        $email = $data['email'] ?? '';
        $subject = $data['subject'] ?? '';
        $body = $data['body'] ?? '';

        $params = [];
        if ($subject) $params[] = 'subject=' . urlencode($subject);
        if ($body) $params[] = 'body=' . urlencode($body);

        $queryString = $params ? '?' . implode('&', $params) : '';

        return "mailto:{$email}{$queryString}";
    }

    protected function generateWhatsAppContent(array $data): string
    {
        $phone = $data['phone'] ?? '';
        $message = $data['message'] ?? '';

        $queryString = $message ? '?text=' . urlencode($message) : '';

        return "https://wa.me/{$phone}{$queryString}";
    }

    protected function generateVCardContent(array $data): string
    {
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $organization = $data['organization'] ?? '';
        $title = $data['title'] ?? '';
        $phone = $data['phone'] ?? '';
        $email = $data['email'] ?? '';
        $website = $data['website'] ?? '';

        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "N:{$lastName};{$firstName};;;\n";
        $vcard .= "FN:{$firstName} {$lastName}\n";
        if ($organization) $vcard .= "ORG:{$organization}\n";
        if ($title) $vcard .= "TITLE:{$title}\n";
        if ($phone) $vcard .= "TEL:{$phone}\n";
        if ($email) $vcard .= "EMAIL:{$email}\n";
        if ($website) $vcard .= "URL:{$website}\n";
        $vcard .= "END:VCARD";

        return $vcard;
    }

    protected function generateSmsContent(array $data): string
    {
        $phone = $data['phone'] ?? '';
        $message = $data['message'] ?? '';

        $queryString = $message ? '?body=' . urlencode($message) : '';

        return "sms:{$phone}{$queryString}";
    }

    protected function generatePhoneContent(array $data): string
    {
        $phone = $data['phone'] ?? '';
        return "tel:{$phone}";
    }

    protected function generateTextContent(array $data): string
    {
        return $data['text'] ?? '';
    }

    protected function generateCalendarContent(array $data): string
    {
        $summary = $data['summary'] ?? '';
        $startDate = $data['start_date'] ?? '';
        $endDate = $data['end_date'] ?? '';
        $location = $data['location'] ?? '';
        $description = $data['description'] ?? '';

        $event = "BEGIN:VEVENT\n";
        $event .= "SUMMARY:{$summary}\n";
        if ($startDate) $event .= "DTSTART:{$startDate}\n";
        if ($endDate) $event .= "DTEND:{$endDate}\n";
        if ($location) $event .= "LOCATION:{$location}\n";
        if ($description) $event .= "DESCRIPTION:{$description}\n";
        $event .= "END:VEVENT";

        return $event;
    }

    protected function generateLocationContent(array $data): string
    {
        $latitude = $data['latitude'] ?? '';
        $longitude = $data['longitude'] ?? '';

        return "geo:{$latitude},{$longitude}";
    }

    protected function generateWebsiteContent(array $data): string
    {
        return $data['url'] ?? $this->destination_url ?? '';
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
