<?php

namespace App\Filament\Resources\QrCodeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Illuminate\Database\Eloquent\Model;

class ScansRelationManager extends RelationManager
{
    protected static string $relationship = 'scans';

    protected static ?string $title = 'Scans';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('scanned_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('device')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('os')
                    ->label('OS')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('browser')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('device')
                    ->options(fn() => $this->getRelationship()
                        ->distinct()
                        ->pluck('device', 'device')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('os')
                    ->options(fn() => $this->getRelationship()
                        ->distinct()
                        ->pluck('os', 'os')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('browser')
                    ->options(fn() => $this->getRelationship()
                        ->distinct()
                        ->pluck('browser', 'browser')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('country')
                    ->options(fn() => $this->getRelationship()
                        ->distinct()
                        ->pluck('country', 'country')
                        ->toArray()),
            ])
            ->defaultSort('scanned_at', 'desc')
            ->bulkActions([
                // Usually no bulk actions needed for scans
            ])
            ->emptyStateHeading('No scans yet')
            ->emptyStateDescription('This QR code hasn\'t been scanned yet.')
            ->emptyStateIcon('heroicon-o-qr-code')
            ->poll('30s')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(false), // Hide create button as scans are system-generated
            ])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
            ])
            ->groups([
                'country',
                'device',
                'browser',
                'os',
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public function isReadOnly(): bool
    {
        return true; // Scans cannot be modified
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === 'dynamic';
    }
}
