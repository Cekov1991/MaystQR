<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCodeScan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QrCodeStats extends BaseWidget
{
    protected function getCards(): array
    {
        $userId = Auth::id();

        return [
            Stat::make('Total Scans', QrCodeScan::whereHas('qrCode', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })->count())
                ->description('All time scans')
                ->descriptionIcon('heroicon-m-qr-code')
                ->chart(QrCodeScan::whereHas('qrCode', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->selectRaw('DATE(scanned_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->limit(30)
                    ->pluck('count')
                    ->toArray()),

            Stat::make('Top Browsers',
                QrCodeScan::whereHas('qrCode', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->select('browser', DB::raw('count(*) as count'))
                    ->whereNotNull('browser')
                    ->groupBy('browser')
                    ->orderByDesc('count')
                    ->first()?->browser ?? 'N/A'
            )
                ->description('Most used browser')
                ->descriptionIcon('heroicon-m-globe-alt'),

            Stat::make('Top Countries',
                QrCodeScan::whereHas('qrCode', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->select('country', DB::raw('count(*) as count'))
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
