<?php

namespace App\Filament\Widgets;

use Filament\Infolists\Components\Section;
use Filament\Widgets\ChartWidget;


// First, create a chart widget:
class QrCodeScanChart extends ChartWidget
{
    protected static string $chartType = 'line';

    protected function getType(): string
    {
        return static::$chartType;
    }

    protected function getData(): array
    {
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
                ],
            ],
            'labels' => $scans->pluck('date')->toArray(),
        ];
    }

}
