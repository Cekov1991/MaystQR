<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

    public function activeAddons()
    {
        return $this->userAddons()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function hasActiveAddon(string $addonKey, ?QrCode $qrCode = null): bool
    {
        return $this->activeAddons()
            ->whereHas('addon', fn($query) => $query->where('key', $addonKey))
            ->when($qrCode, fn($query) => $query->where('qr_code_id', $qrCode->id))
            ->exists();
    }

    public function canPurchaseAddon(Addon $addon, ?QrCode $qrCode = null): bool
    {
        // Check if addon is active
        if (!$addon->is_active) {
            return false;
        }

        // For QR code specific addons, ensure QR code is provided and belongs to user
        if ($qrCode && $addon->type === 'scans') {
            if ($qrCode->user_id !== $this->id) {
                return false;
            }
        }

        // Prevent duplicate purchases for same QR code
        if ($qrCode && $this->hasActiveAddon($addon->key, $qrCode)) {
            return false;
        }

        return true;
    }

    public function getRemainingScans(?QrCode $qrCode = null): int
    {
        $baseLimit = $this->monthly_scan_limit;

        if ($qrCode) {
            // Add additional scans from active scan addons for this QR code
            $additionalScans = $this->activeAddons()
                ->whereHas('addon', fn($query) => $query->where('type', 'scans'))
                ->where('qr_code_id', $qrCode->id)
                ->sum(function ($userAddon) {
                    return json_decode($userAddon->addon->features)[0] ?? 0;
                });

            return $baseLimit + $additionalScans;
        }

        return $baseLimit;
    }

    public function hasAdvancedAnalytics(): bool
    {
        return $this->hasActiveAddon('advanced_analytics');
    }

    public function hasCustomBranding(): bool
    {
        return $this->hasActiveAddon('custom_branding');
    }

    public function hasAdditionalScans(): bool
    {
        return $this->hasActiveAddon('additional_scans_10k');
    }
}
