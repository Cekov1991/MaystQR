<?php

namespace App\Traits;

use App\Models\QrCode;
use Illuminate\Support\Str;

trait HasQrCodes
{
    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function createFreeQrCode(): QrCode
    {
        $shortUrl = Str::random(8);

        return $this->qrCodes()->create([
            'name' => 'My First QR Code',
            'type' => 'dynamic',
            'content' => route('qr.redirect', $shortUrl),
            'short_url' => $shortUrl,
            'destination_url' => 'https://example.com',
            'user_id' => $this->id,
            'options' => [
                'foreground_color' => '#000000',
                'background_color' => '#FFFFFF',
                'size' => 300,
                'margin' => 10,
                'error_correction' => 'M',
            ],
        ]);
    }
}
