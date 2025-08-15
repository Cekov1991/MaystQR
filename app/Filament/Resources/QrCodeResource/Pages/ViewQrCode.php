<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Filament\Resources\QrCodeResource\Widgets\QrCodeScanChart;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use ZipArchive;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

class ViewQrCode extends ViewRecord
{
    protected static string $resource = QrCodeResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->record;

        return $infolist->schema([
            Grid::make(2)->schema([
                Section::make('QR Code')
                    ->schema([
                        ImageEntry::make('qr_code_image')->size(300)->alignCenter()
                    ])
                    ->columnSpan(1),

                Section::make('Details')
                    ->schema([
                        TextEntry::make('name')->weight(FontWeight::Bold),
                        TextEntry::make('type')->badge()->color(
                            fn(string $state): string => match ($state) {
                                'dynamic' => 'success',
                                'static' => 'info',
                            },
                        ),
                        TextEntry::make('qr_content_type')->badge(),
                        TextEntry::make('status')
                            ->state(function ($record) {
                                if ($record->type === 'static') {
                                    return 'Active';
                                }

                                if ($record->isExpired()) {
                                    return 'Expired';
                                }

                                if ($record->isInTrial()) {
                                    return 'Trial Period';
                                }

                                return 'Active';
                            })
                            ->badge()
                            ->color(function ($record) {
                                if ($record->type === 'static') {
                                    return 'success';
                                }

                                if ($record->isExpired()) {
                                    return 'danger';
                                }

                                if ($record->isInTrial()) {
                                    return 'warning';
                                }

                                return 'success';
                            }),
                        TextEntry::make('expires_at')
                            ->label('Expires At')
                            ->formatStateUsing(function ($record) {
                                return $record->expires_at?->format('M j, Y g:i A') ?? 'Not set';
                            })
                            ->color(function ($record) {
                                if ($record->type === 'static') {
                                    return 'success';
                                }

                                if ($record->isExpired()) {
                                    return 'danger';
                                }

                                if ($record->expires_at && $record->expires_at->diffInHours() < 24) {
                                    return 'warning';
                                }

                                return 'primary';
                            })
                            ->visible(fn($record) => $record->type !== 'static'),

                        TextEntry::make('scan_count')
                            ->label('Total Scans')
                            ->visible(fn($record) => $record->type !== 'static'),
                        TextEntry::make('created_at')->dateTime(),
                    ])
                    ->columnSpan(1),
            ]),

            Section::make('Recent Scans')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('scans_today')->label('Scans Today')->state(fn($record) => $record->scans()->whereDate('scanned_at', today())->count()),
                        TextEntry::make('scans_week')->label('Scans This Week')->state(
                            fn($record) => $record
                                ->scans()
                                ->whereBetween('scanned_at', [now()->startOfWeek(), now()->endOfWeek()])
                                ->count(),
                        ),
                        TextEntry::make('unique_countries')->label('Countries')->state(fn($record) => $record->scans()->distinct('country')->count('country')),
                    ]),
                ])
                ->visible(fn($record) => $record->type !== 'static'),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        // Only show analytics chart for dynamic QR codes
        if ($this->record->type === 'static') {
            return [];
        }

        return [
            QrCodeScanChart::make([
                'record' => $this->record,
            ]),
        ];
    }

    public function getFooterWidgetsColumns(): int
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Extend action for dynamic QR codes
        if ($this->record->type === 'dynamic') {
            $actions[] = Action::make('extend')
                ->label('Extend QR Code')
                ->icon('heroicon-o-clock')
                ->color($this->record->isExpired() ? 'danger' : 'warning')
                ->url(route('qr.expired', $this->record->short_url))
                ->visible(fn() => !config('app.free_dynamic_qr_codes'))
                ->openUrlInNewTab();
        }

        $actions[] = Action::make('download_all_formats')
            ->label('Download All Formats')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->action(function () {
                $record = $this->record;
                $zipPath = storage_path("app/temp/{$record->name}-qr-codes.zip");
                $zip = new ZipArchive();

                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    // Add original format
                    $originalPath = Storage::path($record->qr_code_image);
                    $originalFormat = $record->options['format'] ?? 'png';
                    $zip->addFile($originalPath, "qr-{$record->name}.{$originalFormat}");

                    // Generate and add other formats
                    $formats = ['png', 'svg', 'eps'];
                    foreach ($formats as $format) {
                        if ($format === $originalFormat) {
                            continue;
                        }

                        $qrCode = QrCodeGenerator::format($format)
                            ->size($record->options['size'] ?? 300)
                            ->generate($record->content);

                        $tempPath = storage_path("app/temp/qr-{$record->name}.{$format}");
                        file_put_contents($tempPath, $qrCode);
                        $zip->addFile($tempPath, "qr-{$record->name}.{$format}");
                    }

                    $zip->close();

                    // Clean up temporary files
                    foreach ($formats as $format) {
                        if ($format === $originalFormat) {
                            continue;
                        }
                        @unlink(storage_path("app/temp/qr-{$record->name}.{$format}"));
                    }

                    return response()->download($zipPath)->deleteFileAfterSend();
                }
            });

        $actions[] = Action::make('download_original')
            ->label('Download Original')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () {
                return response()->download(Storage::path($this->record->qr_code_image));
            });

        $actions[] = Action::make('edit')->url(fn() => $this->getResource()::getUrl('edit', ['record' => $this->record]));

        return $actions;
    }
}
