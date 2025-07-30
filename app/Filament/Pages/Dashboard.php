<?php

namespace App\Filament\Pages;

use App\Filament\Resources\QrCodeResource\Widgets\RecentScans;
use App\Filament\Resources\QrCodeResource\Widgets\ScansByCountryChart;
use App\Filament\Resources\QrCodeResource\Widgets\ScansByDeviceChart;
use App\Filament\Resources\QrCodeResource\Widgets\StatsOverview;
use App\Filament\Resources\QrCodeResource\Widgets\TopPerformingQrCodes;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Session;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {

        // Check if there's a pending QR code creation
        if (Session::has('pending_qr_code')) {
            $this->redirect('/admin/qr-codes/create-from-session');
            return;
        }

    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            TopPerformingQrCodes::class,
            RecentScans::class,
            ScansByCountryChart::class,
            ScansByDeviceChart::class,
        ];
    }
}