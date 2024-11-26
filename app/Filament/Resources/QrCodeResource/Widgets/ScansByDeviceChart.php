<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCodeScan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ScansByDeviceChart extends ChartWidget
{
    protected static ?string $heading = 'Scans by Device';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = '1';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $data = QrCodeScan::query()
            ->select('device', DB::raw('count(*) as count'))
            ->whereNotNull('device')
            ->groupBy('device')
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
            'labels' => $data->pluck('device')->toArray(),
        ];
    }
}
