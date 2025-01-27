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
}
