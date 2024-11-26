<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }
}
