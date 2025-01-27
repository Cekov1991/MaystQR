<?php

namespace App\Filament\Pages;

use App\Services\SubscriptionService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Placeholder;
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
    protected static ?string $title = 'Manage Subscription';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];
    public float $totalPrice = 0;

    public function mount(): void
    {
        $subscription = auth()->user()->subscription;

        if (!$subscription) {
            $subscription = auth()->user()->createDefaultSubscription();
        }

        $this->form->fill([
            'monthly_scan_limit' => $subscription->monthly_scan_limit,
            'has_advanced_analytics' => $subscription->has_advanced_analytics,
            'has_custom_branding' => $subscription->has_custom_branding,
        ]);

        $this->calculateTotalPrice();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Grid::make(2)->schema([
                    Section::make('Current Plan Features')
                        ->description('Customize your subscription features')
                        ->schema([

                            TextInput::make('monthly_scan_limit')
                                ->label('Monthly Scan Limit')
                                ->numeric()
                                ->minValue(1000)
                                ->step(1000)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn () => $this->calculateTotalPrice())
                                ->helperText('First 1,000 scans are free, then $2/1000 scans'),

                            Toggle::make('has_advanced_analytics')
                                ->label('Advanced Analytics')
                                ->reactive()
                                ->afterStateUpdated(fn () => $this->calculateTotalPrice())
                                ->helperText('Enable detailed analytics ($20/month)'),

                            Toggle::make('has_custom_branding')
                                ->label('Custom Branding')
                                ->reactive()
                                ->afterStateUpdated(fn () => $this->calculateTotalPrice())
                                ->helperText('Enable custom branding ($10/month)'),
                        ]),

                    Section::make('Subscription Summary')
                        ->schema([
                            View::make('filament.pages.partials.subscription-summary')
                                ->viewData([
                                    'totalPrice' => $this->totalPrice,
                                ])
                        ])
                ]),
            ]);
    }

    public function calculateTotalPrice(): void
    {
        $data = $this->form->getState();
        $this->totalPrice = 0;

        // Calculate scans cost
        if ($data['monthly_scan_limit'] > 1000) {
            $this->totalPrice += ceil(($data['monthly_scan_limit'] - 1000) / 1000) * 2.00;
        }

        // Add feature costs
        if ($data['has_advanced_analytics']) {
            $this->totalPrice += 20.00;
        }
        if ($data['has_custom_branding']) {
            $this->totalPrice += 10.00;
        }
    }

    public function update(): void
    {
        $data = $this->form->getState();

        try {
            $subscription = auth()->user()->subscription;
            $subscriptionService = app(SubscriptionService::class);

            foreach ($data as $key => $value) {
                if ($subscription->$key !== $value) {
                    $subscriptionService->addFeature($subscription, $key, $value, $this->calculateFeaturePrice($key, $value));
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
        return match($key) {
            'monthly_scan_limit' => ceil(($value - 1000) / 1000) * 2.00,
            'has_advanced_analytics' => $value ? 20.00 : 0,
            'has_custom_branding' => $value ? 10.00 : 0,
            default => 0,
        };
    }
}
