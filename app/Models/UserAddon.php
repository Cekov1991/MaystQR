<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddon extends Model
{
    protected $fillable = [
        'user_id',
        'addon_id',
        'qr_code_id',
        'paypal_subscription_id',
        'status',
        'expires_at',
        'settings',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class);
    }
}
