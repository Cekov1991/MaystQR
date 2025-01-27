<?php

namespace App\Filament\Resources\StaticQrCodeResource\Pages;

use App\Filament\Resources\StaticQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaticQrCode extends EditRecord
{
    protected static string $resource = StaticQrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
