<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCodeScan;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class RecentScans extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Recent Scans';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                QrCodeScan::with('qrCode')
                    ->latest('scanned_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('qrCode.name')
                    ->label('QR Code')
                    ->searchable(),
                TextColumn::make('browser')
                    ->searchable(),
                TextColumn::make('os')
                    ->label('OS')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('scanned_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->poll('15s');
    }
}
