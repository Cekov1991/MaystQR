<?php

namespace App\Models;

use Database\Factories\QrCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use SimpleSoftwareIO\QrCode\Generator;

class QrCode extends Model
{
    /** @use HasFactory<QrCodeFactory> */
    use HasFactory;

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

    const QR_STYLES = [
        'round' => 'Rounded',
        'square' => 'Classic squares',
        'round_circle' => 'Circle eyes',
        'dot' => 'Dots',
    ];

    const DEFAULT_STYLE = 'round';

    /**
     * Deliberately tiny payload: fewer modules means larger ones, which is what
     * makes the difference between the styles readable at thumbnail size.
     */
    const STYLE_SAMPLE_CONTENT = 'EasyQR';

    const PROHIBITED_DOMAINS = [
        // URL shorteners (to prevent redirect chains)
        'bit.ly', 'tinyurl.com', 'short.link', 'ow.ly', 't.co', 'goo.gl',
        'tiny.cc', 'is.gd', 'buff.ly', 'rebrand.ly', 'shorturl.at',

        // Known malicious/suspicious patterns
        'free-stuff', 'click-here', 'urgent-action',

        // Add more as needed
    ];

    const PROHIBITED_KEYWORDS = [
        'phishing', 'scam', 'malware', 'virus', 'hack',
        'free-money', 'click-here-now', 'urgent-action',
        'download-now', 'claim-prize', 'congratulations-winner',
    ];

    const PROHIBITED_EXTENSIONS = [
        '.exe', '.bat', '.cmd', '.pif', '.scr', '.vbs',
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
            if (! $qrCode->short_url) {
                $qrCode->short_url = static::generateUniqueShortUrl();
            }

            if (! $qrCode->user_id) {
                $qrCode->user_id = auth()->id();
            }

            if (! $qrCode->type) {
                $qrCode->type = 'static';
            }

            if (! $qrCode->qr_content_type) {
                $qrCode->qr_content_type = 'website';
            }

            // Generate content based on QR type
            $qrCode->content = $qrCode->generateContentFromType();

            // Dynamic QR codes encode the MaystQR short URL, so every scan resolves through us
            if ($qrCode->type === 'dynamic') {
                $qrCode->content = route('qr.redirect', $qrCode->short_url);
            }

            // Generate QR code image
            $qrCode->generateQrCode();
        });

        static::updating(function ($qrCode) {
            // Static QR codes should be completely immutable
            if ($qrCode->type === 'static') {
                throw new \Exception('Static QR codes cannot be updated after creation to preserve printed codes.');
            }

            // Dynamic QR codes: allow content updates but never regenerate the QR code itself
            if ($qrCode->type === 'dynamic') {
                // Allow updating destination_url and qr_content_data
                // But prevent changes that would regenerate the QR code image
                if ($qrCode->isDirty(['content', 'options', 'qr_code_path', 'qr_code_image'])) {
                    throw new \Exception('QR code image cannot be changed after creation to preserve printed codes.');
                }

                // Content updates are fine for dynamic codes since they go through your backend
                // No need to regenerate anything - just update the database
            }
        });

    }

    public function getFormatedContentAttribute(): string
    {
        return match ($this->qr_content_type) {
            'wifi' => $this->generateWifiContent($this->qr_content_data),
            'email' => $this->generateEmailContent($this->qr_content_data),
            'whatsapp' => $this->generateWhatsAppContent($this->qr_content_data),
            'vcard' => $this->generateVCardContent($this->qr_content_data),
            'sms' => $this->generateSmsContent($this->qr_content_data),
            'phone' => $this->generatePhoneContent($this->qr_content_data),
            'text' => $this->generateTextContent($this->qr_content_data),
            'calendar' => $this->generateCalendarContent($this->qr_content_data),
            'location' => $this->generateLocationContent($this->qr_content_data),
            'website' => $this->generateWebsiteContent($this->qr_content_data),
            default => $this->destination_url ?? '',
        };
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
        if ($subject) {
            $params[] = 'subject='.urlencode($subject);
        }
        if ($body) {
            $params[] = 'body='.urlencode($body);
        }

        $queryString = $params ? '?'.implode('&', $params) : '';

        return "mailto:{$email}{$queryString}";
    }

    protected function generateWhatsAppContent(array $data): string
    {
        $phone = $data['phone'] ?? '';
        $message = $data['message'] ?? '';

        $queryString = $message ? '?text='.urlencode($message) : '';

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
        if ($organization) {
            $vcard .= "ORG:{$organization}\n";
        }
        if ($title) {
            $vcard .= "TITLE:{$title}\n";
        }
        if ($phone) {
            $vcard .= "TEL:{$phone}\n";
        }
        if ($email) {
            $vcard .= "EMAIL:{$email}\n";
        }
        if ($website) {
            $vcard .= "URL:{$website}\n";
        }
        $vcard .= 'END:VCARD';

        return $vcard;
    }

    protected function generateSmsContent(array $data): string
    {
        $phone = $data['phone'] ?? '';
        $message = $data['message'] ?? '';

        $queryString = $message ? '?body='.urlencode($message) : '';

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
        if ($startDate) {
            $event .= "DTSTART:{$startDate}\n";
        }
        if ($endDate) {
            $event .= "DTEND:{$endDate}\n";
        }
        if ($location) {
            $event .= "LOCATION:{$location}\n";
        }
        if ($description) {
            $event .= "DESCRIPTION:{$description}\n";
        }
        $event .= 'END:VEVENT';

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

    /**
     * Builds a generator configured from a stored options array, so every place
     * that renders a QR code produces the same image for the same record.
     *
     * @param  array{format?: string, size?: int, color?: string, errorCorrection?: string, style?: string}  $options
     */
    public static function buildGenerator(array $options): Generator
    {
        [$r, $g, $b] = sscanf($options['color'] ?? '#000000', '#%02x%02x%02x') ?? [0, 0, 0];

        $generator = QrCodeGenerator::format($options['format'] ?? 'png')
            ->size($options['size'] ?? 300)
            ->errorCorrection($options['errorCorrection'] ?? 'M')
            ->color($r, $g, $b);

        return static::applyStyle($generator, $options['style'] ?? self::DEFAULT_STYLE);
    }

    /**
     * Applies a module and eye shape to a generator.
     *
     * The 'round' style deliberately leaves the eye style unset so the finder
     * patterns inherit the rounded module shape. The 'dot' style must set an
     * eye style explicitly, otherwise the finder patterns render as loose dots
     * and scanners can no longer locate the symbol.
     */
    protected static function applyStyle(Generator $generator, string $style): Generator
    {
        return match ($style) {
            'square' => $generator,
            'round_circle' => $generator->style('round', 0.5)->eye('circle'),
            'dot' => $generator->style('dot', 0.85)->eye('circle'),
            default => $generator->style('round', 0.5),
        };
    }

    /**
     * Renders a small sample of a style as an inline SVG data URI, for the
     * visual style picker on the create form.
     */
    public static function styleSample(string $style): string
    {
        $key = 'qr-style-sample:'.$style.':'.substr(md5(self::STYLE_SAMPLE_CONTENT), 0, 8);

        return Cache::rememberForever($key, function () use ($style): string {
            $svg = (string) static::buildGenerator([
                'format' => 'svg',
                'size' => 160,
                'style' => $style,
            ])->generate(self::STYLE_SAMPLE_CONTENT);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        });
    }

    protected function generateQrCode(): void
    {
        $options = $this->options ?? [];
        $format = $options['format'] ?? 'png';

        $qrCode = static::buildGenerator($options)->generate($this->content);

        // Generate unique filename
        $filename = 'qr-codes/'.uniqid().'.'.$format;

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

    public static function validateUrl($url): array
    {
        if (empty($url)) {
            return [
                'valid' => false,
                'message' => 'Please enter a valid URL.',
            ];
        }

        // Parse the URL
        $parsedUrl = parse_url($url);
        if (! $parsedUrl || ! isset($parsedUrl['host'])) {
            return [
                'valid' => false,
                'message' => 'Invalid URL format. Please enter a complete URL (e.g., https://example.com)',
            ];
        }

        $domain = strtolower($parsedUrl['host']);
        $fullUrl = strtolower($url);

        // Must use HTTPS for external websites (except localhost for development)
        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://localhost') && ! str_starts_with($url, 'http://127.0.0.1')) {
            return [
                'valid' => false,
                'message' => 'For security reasons, only HTTPS URLs are allowed. Please use https:// instead of http://',
            ];
        }

        // Check against prohibited domains
        foreach (self::PROHIBITED_DOMAINS as $prohibitedDomain) {
            if (str_contains($domain, strtolower($prohibitedDomain))) {
                return [
                    'valid' => false,
                    'message' => "URL shortening services like '{$prohibitedDomain}' are not allowed. Please use the direct URL to your content.",
                ];
            }
        }

        // Check URL for prohibited keywords
        foreach (self::PROHIBITED_KEYWORDS as $keyword) {
            if (str_contains($fullUrl, $keyword)) {
                return [
                    'valid' => false,
                    'message' => "URLs containing '{$keyword}' are not permitted for security reasons. Please use a different URL.",
                ];
            }
        }

        // Check for prohibited file extensions
        $path = $parsedUrl['path'] ?? '';
        foreach (self::PROHIBITED_EXTENSIONS as $extension) {
            if (str_ends_with(strtolower($path), strtolower($extension))) {
                return [
                    'valid' => false,
                    'message' => "Executable files ({$extension}) are not allowed for security reasons. Please link to a webpage instead.",
                ];
            }
        }

        // Block IP addresses (except localhost)
        if (preg_match('/^https?:\/\/\d+\.\d+\.\d+\.\d+/', $url) && ! str_starts_with($url, 'http://127.0.0.1') && ! str_starts_with($url, 'http://localhost')) {
            return [
                'valid' => false,
                'message' => 'Direct IP addresses are not allowed for security reasons. Please use a proper domain name.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'URL is valid',
        ];
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

    public function isDynamic(): bool
    {
        return $this->type === 'dynamic';
    }

    public function isStatic(): bool
    {
        return $this->type === 'static';
    }
}
