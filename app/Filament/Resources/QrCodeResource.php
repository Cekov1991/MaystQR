<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QrCodeResource\Pages;
use App\Filament\Resources\QrCodeResource\RelationManagers;
use App\Models\QrCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Notifications\Notification;
use App\Filament\Resources\QrCodeResource\RelationManagers\ScansRelationManager;
use Filament\Facades\Filament;

class QrCodeResource extends Resource
{
    protected static ?string $model = QrCode::class;

    protected static ?string $navigationIcon = 'heroicon-m-qr-code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'static' => 'Static QR Code',
                                        'dynamic' => 'Dynamic QR Code (Premium)',
                                    ])
                                    ->default('static')
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null)
                                    ->dehydrated(),
                            ])
                    ]),

                Section::make('URL Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('destination_url')
                            ->label('Destination URL')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->disabled(fn ($record) => $record?->type === 'static' && $record !== null)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('content')
                            ->required()
                            ->maxLength(2048)
                            ->label('QR Code Content')
                            ->helperText('For static QRs, this will be the fixed content. For dynamic QRs, this can be updated.'),
                    ]),

                Section::make('QR Code Appearance')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('options.format')
                                    ->label('Image Format')
                                    ->options([
                                        'png' => 'PNG',
                                        'svg' => 'SVG',
                                        'eps' => 'EPS',
                                    ])
                                    ->default('png')
                                    ->required(),

                                Forms\Components\ColorPicker::make('options.color')
                                    ->label('QR Code Color')
                                    ->default('#000000'),

                                Forms\Components\Select::make('options.errorCorrection')
                                    ->label('Error Correction')
                                    ->options([
                                        'L' => 'Low (7%)',
                                        'M' => 'Medium (15%)',
                                        'Q' => 'Quartile (25%)',
                                        'H' => 'High (30%)',
                                    ])
                                    ->default('M'),

                                Forms\Components\TextInput::make('options.size')
                                    ->label('Size (px)')
                                    ->numeric()
                                    ->default(300)
                                    ->minValue(100)
                                    ->maxValue(2000),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('qr_code_image')
                    ->label('QR Code')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'danger' => 'static',
                        'success' => 'dynamic',
                    ]),
                Tables\Columns\TextColumn::make('short_url')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('scan_count')
                    ->label('Scans')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'static' => 'Static',
                        'dynamic' => 'Dynamic',
                    ])
            ])
            ->actions([
                TableAction::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Download QR Code')
                    ->action(function (QrCode $record) {
                        // Check rate limit
                        if (RateLimiter::tooManyAttempts('qr-downloads:'.auth()->id(), 60)) {
                            $seconds = RateLimiter::availableIn('qr-downloads:'.auth()->id());
                            Notification::make()
                                ->danger()
                                ->title('Download limit reached')
                                ->body("Please try again in {$seconds} seconds.")
                                ->send();
                            return;
                        }

                        // Add to rate limiter
                        RateLimiter::hit('qr-downloads:'.auth()->id());

                        return response()->download(
                            Storage::disk('public')->path($record->qr_code_image)
                        );
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            ScansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQrCodes::route('/'),
            'create' => Pages\CreateQrCode::route('/create'),
            'view' => Pages\ViewQrCode::route('/{record}'),
            'edit' => Pages\EditQrCode::route('/{record}/edit'),
        ];
    }
}
