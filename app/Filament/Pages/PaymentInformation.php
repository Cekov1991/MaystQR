<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class PaymentInformation extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Account';
    protected static string $view = 'filament.pages.payment-information';
    protected static ?string $title = 'Payment Information';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];
    public bool $hasPayPal = false;

    public function mount(): void
    {
        $this->hasPayPal = $this->user->paymentMethods()
            ->where('provider', 'paypal')
            ->exists();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('PayPal Information')
                    ->description('Connect your PayPal account for automatic billing.')
                    ->schema([
                        // We'll use a custom view for the PayPal button
                    ]),
            ]);
    }

    public function save(): void
    {
        // Here you would integrate with your payment processor (e.g., Stripe)
        // For now, we'll just show a success message

        Notification::make()
            ->success()
            ->title('Payment information saved successfully')
            ->send();
    }

    public function handlePayPalSuccess(array $data): void
    {
        $paymentMethod = $this->user->paymentMethods()->create([
            'provider' => 'paypal',
            'provider_id' => $data['subscriptionID'],
            'email' => $data['payerEmail'] ?? null,
            'is_default' => !$this->user->paymentMethods()->exists(), // First one is default
            'meta' => [
                'payer_id' => $data['payerID'] ?? null,
                // Add other PayPal-specific data here
            ],
        ]);

        Notification::make()
            ->success()
            ->title('PayPal connected successfully')
            ->send();
    }

    public function disconnectPayPal(): void
    {
        $this->user->disconnectPaymentMethod('paypal');
        $this->hasPayPal = false;

        Notification::make()
            ->success()
            ->title('PayPal disconnected successfully')
            ->send();
    }
}
