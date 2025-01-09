<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'user_addon_id',
        'paypal_payment_id',
        'amount',
        'currency',
        'status',
        'paypal_response',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userAddon()
    {
        return $this->belongsTo(UserAddon::class);
    }
}

