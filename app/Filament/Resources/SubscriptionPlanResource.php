<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PayPalService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\ActionSize;
use Filament\Notifications\Notification;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Subscription Plans';
    protected static ?string $navigationGroup = 'Account';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('dynamic_qr_limit')
                    ->label('Dynamic QR Codes')
                    ->badge()
                    ->formatStateUsing(fn ($state) => "{$state} codes"),
                TextColumn::make('scans_per_code')
                    ->label('Scans per Code')
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format($state) . ' scans'),
                TextColumn::make('is_active')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
            ])
            ->actions([
                Action::make('subscribe')
                    ->button()
                    ->label('Subscribe')
                    ->color('primary')
                    ->icon('heroicon-m-credit-card')
                    ->action(function (SubscriptionPlan $record, PayPalService $paypalService) {
                        // Check if user has payment method
                        if (!auth()->user()->hasPaymentMethod()) {
                            Notification::make()
                                ->warning()
                                ->title('Payment Method Required')
                                ->body('Please add a payment method before subscribing.')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('add_payment_method')
                                        ->button()
                                        ->url(route('filament.admin.resources.payment-methods.index'))
                                        ->label('Add Payment Method'),
                                ])
                                ->send();
                            return;
                        }

                        try {
                            // Create PayPal order
                            $order = $paypalService->createSubscription($record->price, $record->id);

                            // Create pending subscription
                            Subscription::create([
                                'user_id' => auth()->id(),
                                'subscription_plan_id' => $record->id,
                                'status' => 'pending',
                                'dynamic_qr_limit' => $record->dynamic_qr_limit,
                                'scans_per_code' => $record->scans_per_code,
                                'current_price' => $record->price,
                                'next_billing_date' => now()->addMonth(),
                            ]);

                            // Redirect to PayPal checkout
                            return redirect($order['links'][1]['href']);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Subscription Failed')
                                ->body('Unable to process subscription. Please try again.')
                                ->send();
                        }
                    })
                    ->visible(fn (SubscriptionPlan $record): bool =>
                        !auth()->user()->subscription()->exists() && $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Subscribe to Plan')
                    ->modalDescription(fn (SubscriptionPlan $record): string =>
                        "Are you sure you want to subscribe to the {$record->name}? You will be charged {$record->price}$ monthly.")
                    ->modalSubmitActionLabel('Confirm Subscription'),
            ])
            ->defaultSort('price')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
        ];
    }
}
