<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCode;
use Filament\Widgets\ChartWidget;

class QrCodeScanChart extends ChartWidget
{
    public ?QrCode $record = null;

    protected static ?string $heading = 'Scan Trends';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        if (!$this->record) {
            return [];
        }

        $scans = $this->record->scans()
            ->selectRaw('DATE(scanned_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Scans',
                    'data' => $scans->pluck('count')->toArray(),
                    'borderColor' => '#7c3aed',
                    'tension' => 0.4,
                    'fill' => false,
                ],
            ],
            'labels' => $scans->pluck('date')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
