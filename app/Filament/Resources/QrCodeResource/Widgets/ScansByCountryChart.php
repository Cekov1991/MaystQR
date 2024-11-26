<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCodeScan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ScansByCountryChart extends ChartWidget
{
    protected static ?string $heading = 'Scans by Country';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = '1';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $data = QrCodeScan::query()
            ->select('country', DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        '#7c3aed', '#06b6d4', '#10b981', '#f59e0b', '#ef4444'
                    ],
                ],
            ],
            'labels' => $data->pluck('country')->toArray(),
        ];
    }
}
