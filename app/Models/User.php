<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function userAddons()
    {
        return $this->hasMany(UserAddon::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function getRemainingScans(): int
    {
        return $this->subscription?->monthly_scan_limit ?? 1000; // Free tier limit
    }

    public function hasAdvancedAnalytics(): bool
    {
        return $this->subscription?->has_advanced_analytics ?? false;
    }

    public function canCreateDynamicQrCode(): bool
    {
        if (!$this->subscription) {
            return $this->qrCodes()->where('type', 'dynamic')->count() < 1;
        }

        return $this->qrCodes()->where('type', 'dynamic')->count() < $this->subscription->dynamic_qr_codes_limit;
    }
}
