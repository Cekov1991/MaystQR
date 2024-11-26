<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCodeScan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class QrCodeStats extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Stat::make('Total Scans', QrCodeScan::count())
                ->description('All time scans')
                ->descriptionIcon('heroicon-m-qr-code')
                ->chart(QrCodeScan::query()
                    ->selectRaw('DATE(scanned_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->limit(30)
                    ->pluck('count')
                    ->toArray()),

            Stat::make('Top Browsers',
                QrCodeScan::select('browser', DB::raw('count(*) as count'))
                    ->whereNotNull('browser')
                    ->groupBy('browser')
                    ->orderByDesc('count')
                    ->first()?->browser ?? 'N/A'
            )
                ->description('Most used browser')
                ->descriptionIcon('heroicon-m-globe-alt'),

            Stat::make('Top Countries',
                QrCodeScan::select('country', DB::raw('count(*) as count'))
                    ->whereNotNull('country')
                    ->groupBy('country')
                    ->orderByDesc('count')
                    ->first()?->country ?? 'N/A'
            )
                ->description('Most scans from')
                ->descriptionIcon('heroicon-m-map-pin'),
        ];
    }
}
