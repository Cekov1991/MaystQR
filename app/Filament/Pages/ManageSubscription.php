<?php

namespace App\Filament\Pages;

use App\Services\SubscriptionService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSubscription extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Account';
    protected static string $view = 'filament.pages.manage-subscription';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $subscription = auth()->user()->subscription;

        $this->form->fill([
            'dynamic_qr_codes_limit' => $subscription?->dynamic_qr_codes_limit ?? 1,
            'monthly_scan_limit' => $subscription?->monthly_scan_limit ?? 1000,
            'has_advanced_analytics' => $subscription?->has_advanced_analytics ?? false,
            'has_custom_branding' => $subscription?->has_custom_branding ?? false,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Subscription Features')
                    ->description('Customize your subscription features')
                    ->schema([
                        TextInput::make('dynamic_qr_codes_limit')
                            ->label('Dynamic QR Code Limit')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Number of dynamic QR codes you can create'),

                        TextInput::make('monthly_scan_limit')
                            ->label('Monthly Scan Limit')
                            ->numeric()
                            ->minValue(1000)
                            ->step(1000)
                            ->required()
                            ->helperText('Number of scans allowed per month'),

                        Toggle::make('has_advanced_analytics')
                            ->label('Advanced Analytics')
                            ->helperText('Enable detailed analytics and reporting'),

                        Toggle::make('has_custom_branding')
                            ->label('Custom Branding')
                            ->helperText('Enable custom branding on your QR codes'),
                    ])
                    ->columns(2),
            ]);
    }

    public function update(): void
    {
        $data = $this->form->getState();

        try {
            $subscription = auth()->user()->subscription;
            $subscriptionService = app(SubscriptionService::class);

            if (!$subscription) {
                $subscription = $subscriptionService->createSubscription(auth()->user());
            }

            foreach ($data as $key => $value) {
                if ($subscription->$key !== $value) {
                    $price = $this->calculateFeaturePrice($key, $value);
                    $subscriptionService->addFeature($subscription, $key, $value, $price);
                }
            }

            Notification::make()
                ->success()
                ->title('Subscription updated successfully')
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error updating subscription')
                ->body($e->getMessage())
                ->send();
        }
    }

    private function calculateFeaturePrice(string $key, int|bool $value): float
    {
        // Implement your pricing logic here
        return match($key) {
            'dynamic_qr_codes_limit' => ($value - 1) * 5.00, // $5 per additional QR code
            'monthly_scan_limit' => ceil(($value - 1000) / 1000) * 2.00, // $2 per 1000 additional scans
            'has_advanced_analytics' => $value ? 20.00 : 0, // $20 for advanced analytics
            'has_custom_branding' => $value ? 10.00 : 0, // $10 for custom branding
            default => 0,
        };
    }
}
