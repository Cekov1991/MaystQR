<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Services\PayPalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'My Subscription';

    protected static ?string $navigationGroup = 'Account';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription_plan.name')
                    ->label('Plan')
                    ->sortable(),
                TextColumn::make('current_price')
                    ->money('USD')
                    ->label('Price')
                    ->sortable(),
                TextColumn::make('dynamic_qr_limit')
                    ->label('Dynamic QR Limit')
                    ->sortable(),
                TextColumn::make('scans_per_code')
                    ->label('Scans per QR')
                    ->sortable(),
                TextColumn::make('next_billing_date')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
            ])
            ->actions([
                Action::make('cancel')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Subscription $record): bool =>
                        $record->status === 'active'
                    )
                    ->action(function (Subscription $record, PayPalService $paypalService): void {
                        $record->update(['status' => 'cancelled']);
                        $paypalService->cancel($record->order_id);
                    })
                    ->modalHeading('Cancel Subscription')
                    ->modalDescription('Are you sure you want to cancel your subscription? You will continue to have access until the end of your billing period.')
                    ->modalSubmitActionLabel('Yes, cancel subscription'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        // Only show the current user's subscription
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
        ];
    }
}
