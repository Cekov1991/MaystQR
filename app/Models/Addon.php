<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = [
        'name',
        'type',
        'key',
        'price',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function userAddons()
    {
        return $this->hasMany(UserAddon::class);
    }
}
