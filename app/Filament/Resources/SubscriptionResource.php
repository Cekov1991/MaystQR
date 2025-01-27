<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Account';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\TextInput::make('dynamic_qr_codes_limit')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Dynamic QR Code Limit'),

                        Forms\Components\TextInput::make('monthly_scan_limit')
                            ->required()
                            ->numeric()
                            ->minValue(1000)
                            ->label('Monthly Scan Limit'),

                        Forms\Components\Toggle::make('has_advanced_analytics')
                            ->label('Advanced Analytics')
                            ->helperText('Enable advanced analytics features'),

                        Forms\Components\Toggle::make('has_custom_branding')
                            ->label('Custom Branding')
                            ->helperText('Enable custom branding features'),

                        Forms\Components\TextInput::make('current_price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('next_billing_date')
                            ->label('Next Billing Date')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('dynamic_qr_codes_limit')
                    ->label('QR Codes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_scan_limit')
                    ->label('Scan Limit')
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_advanced_analytics')
                    ->label('Analytics')
                    ->boolean(),

                Tables\Columns\IconColumn::make('has_custom_branding')
                    ->label('Branding')
                    ->boolean(),

                Tables\Columns\TextColumn::make('current_price')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_billing_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'cancelled',
                        'warning' => 'expired',
                        'success' => 'active',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn (Subscription $record) => $record->update(['status' => 'cancelled']))
                    ->visible(fn (Subscription $record) => $record->status === 'active'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->latest();
    }
}
