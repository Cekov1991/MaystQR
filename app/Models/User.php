<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasSubscription;
use App\Traits\HasQrCodes;
use App\Traits\HasDynamicQrCodePricing;

class User extends Authenticatable
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasSubscription, HasQrCodes, HasDynamicQrCodePricing;

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

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function hasPaymentInformation(): bool
    {
        return $this->paymentMethods()->exists();
    }

    public function getDefaultPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethods()->where('is_default', true)->first();
    }

    public function disconnectPaymentMethod(string $provider): void
    {
        $this->paymentMethods()->where('provider', $provider)->delete();
    }
}
