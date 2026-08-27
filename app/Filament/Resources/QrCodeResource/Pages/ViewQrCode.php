<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Filament\Resources\QrCodeResource\Widgets\QrCodeScanChart;
use App\Models\QrCode;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

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
                        ImageEntry::make('qr_code_image')->size(300)->alignCenter(),
                    ])
                    ->columnSpan(1),

                Section::make('Details')
                    ->schema([
                        TextEntry::make('name')->weight(FontWeight::Bold),
                        TextEntry::make('type')->badge()->color(
                            fn (string $state): string => match ($state) {
                                'dynamic' => 'success',
                                'static' => 'info',
                            },
                        ),
                        TextEntry::make('qr_content_type')->badge(),
                        TextEntry::make('scan_count')
                            ->label('Total Scans')
                            ->visible(fn ($record) => $record->type !== 'static'),
                        TextEntry::make('created_at')->dateTime(),
                    ])
                    ->columnSpan(1),
            ]),

            Section::make('Recent Scans')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('scans_today')->label('Scans Today')->state(fn ($record) => $record->scans()->whereDate('scanned_at', today())->count()),
                        TextEntry::make('scans_week')->label('Scans This Week')->state(
                            fn ($record) => $record
                                ->scans()
                                ->whereBetween('scanned_at', [now()->startOfWeek(), now()->endOfWeek()])
                                ->count(),
                        ),
                        TextEntry::make('unique_countries')->label('Countries')->state(fn ($record) => $record->scans()->distinct('country')->count('country')),
                    ]),
                ])
                ->visible(fn ($record) => $record->type !== 'static'),
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

        $actions[] = Action::make('download_all_formats')
            ->label('Download All Formats')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->action(function () {
                $record = $this->record;
                $baseName = 'qr-'.$this->downloadFileName($record->name);
                $zipPath = tempnam(sys_get_temp_dir(), 'qr-codes-');
                $zip = new ZipArchive;

                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    return null;
                }

                $originalFormat = QrCode::effectiveFormat($record->options ?? []);

                foreach ($this->downloadableFormats($record) as $format) {
                    $zip->addFromString(
                        "{$baseName}.{$format}",
                        $format === $originalFormat
                            ? $this->originalImageContents($record, $originalFormat)
                            : (string) QrCode::buildGenerator(
                                array_merge($record->options ?? [], ['format' => $format])
                            )->generate($record->content),
                    );
                }

                $zip->close();

                return response()
                    ->download($zipPath, "{$this->downloadFileName($record->name)}-qr-codes.zip")
                    ->deleteFileAfterSend();
            });

        $actions[] = Action::make('download_original')
            ->label('Download Original')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () {
                $record = $this->record;
                $format = QrCode::effectiveFormat($record->options ?? []);

                return response()->streamDownload(
                    fn () => print ($this->originalImageContents($record, $format)),
                    'qr-'.$this->downloadFileName($record->name).".{$format}",
                );
            });

        $actions[] = Action::make('edit')->url(fn () => $this->getResource()::getUrl('edit', ['record' => $this->record]));

        return $actions;
    }

    /**
     * The library only composites a centre logo onto a PNG, so a code with a
     * logo bundles the PNG alone rather than shipping SVG and EPS siblings
     * that quietly drop it.
     *
     * @return array<int, string>
     */
    protected function downloadableFormats(QrCode $record): array
    {
        return QrCode::hasLogo($record->options ?? [])
            ? ['png']
            : ['png', 'svg', 'eps'];
    }

    /**
     * Reads the stored image through the filesystem disk rather than a local
     * path, so downloads work on cloud disks such as S3. Regenerates the image
     * if the stored object has gone missing.
     */
    protected function originalImageContents(QrCode $record, string $format): string
    {
        $contents = $record->qr_code_image
            ? Storage::get($record->qr_code_image)
            : null;

        if ($contents !== null && $contents !== '') {
            return $contents;
        }

        return (string) QrCode::buildGenerator(
            array_merge($record->options ?? [], ['format' => $format])
        )->generate($record->content);
    }

    protected function downloadFileName(string $name): string
    {
        $name = trim(str_replace(['/', '\\', "\0"], '-', $name));

        return $name === '' ? 'qr-code' : $name;
    }
}
