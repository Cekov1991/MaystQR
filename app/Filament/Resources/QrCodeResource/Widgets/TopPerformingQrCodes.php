<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCode;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Auth;

class TopPerformingQrCodes extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                QrCode::where('user_id', Auth::id())
                    ->withCount('scans')
                    ->orderByDesc('scans_count')
                    ->limit(5)
            )
            ->columns([
                ImageColumn::make('qr_code_image')
                    ->square()
                    ->size(40),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dynamic' => 'success',
                        'static' => 'info',
                    }),
                TextColumn::make('scans_count')
                    ->label('Total Scans')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->heading('Top Performing QR Codes');
    }
}
