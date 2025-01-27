<?php

namespace App\Filament\Resources\StaticQrCodeResource\Pages;

use App\Filament\Resources\StaticQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaticQrCodes extends ListRecords
{
    protected static string $resource = StaticQrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
