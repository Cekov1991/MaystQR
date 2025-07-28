<?php

namespace App\Filament\Resources\QrCodeResource\Widgets;

use App\Models\QrCode;
use App\Models\QrCodeScan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        return [
            Stat::make('Total QR Codes', QrCode::where('user_id', $userId)->count())
                ->description('Your active QR codes')
                ->descriptionIcon('heroicon-m-qr-code')
                ->chart(QrCode::where('user_id', $userId)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->limit(7)
                    ->pluck('count')
                    ->toArray()),

            Stat::make('Total Scans', QrCodeScan::whereHas('qrCode', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })->count())
                ->description('All time scans')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->chart(QrCodeScan::whereHas('qrCode', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->selectRaw('DATE(scanned_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->limit(7)
                    ->pluck('count')
                    ->toArray()),

            Stat::make('Today\'s Scans', QrCodeScan::whereDate('scanned_at', today())
                ->whereHas('qrCode', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })->count())
                ->description('Scans in last 24 hours')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
