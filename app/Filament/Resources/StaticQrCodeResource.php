<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaticQrCodeResource\Pages;
use App\Models\QrCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Storage;

class StaticQrCodeResource extends Resource
{
    protected static ?string $model = QrCode::class;
    protected static ?string $navigationIcon = 'heroicon-m-qr-code';
    protected static ?string $navigationLabel = 'Static QR Codes';
    protected static ?string $navigationGroup = 'QR Codes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Hidden::make('type')
                                    ->default('static'),
                            ]),
                        Forms\Components\Select::make('folder_id')
                            ->relationship('folder', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Folder'),
                    ]),

                Section::make('URL Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('destination_url')
                            ->label('URL')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
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
            ->query(QrCode::query()->where('type', 'static'))
            ->columns([
                Tables\Columns\ImageColumn::make('qr_code_image')
                    ->label('QR Code')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination_url')
                    ->label('URL')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('scan_count')
                    ->label('Scans')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('folder')
                    ->relationship('folder', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Download QR Code')
                    ->action(function (QrCode $record) {
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticQrCodes::route('/'),
            'create' => Pages\CreateStaticQrCode::route('/create'),
            'edit' => Pages\EditStaticQrCode::route('/{record}/edit'),
        ];
    }
}
