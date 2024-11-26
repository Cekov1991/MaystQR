<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCode;
use App\Models\QrCodeScan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total QR Codes', QrCode::count())
                ->description('Total active QR codes')
                ->descriptionIcon('heroicon-m-qr-code')
                ->chart(QrCode::query()
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->limit(7)
                    ->pluck('count')
                    ->toArray()),

            Stat::make('Total Scans', QrCodeScan::count())
                ->description('All time scans')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->chart(QrCodeScan::query()
                    ->selectRaw('DATE(scanned_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->limit(7)
                    ->pluck('count')
                    ->toArray()),

            Stat::make('Today\'s Scans', QrCodeScan::whereDate('scanned_at', today())->count())
                ->description('Scans in last 24 hours')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
