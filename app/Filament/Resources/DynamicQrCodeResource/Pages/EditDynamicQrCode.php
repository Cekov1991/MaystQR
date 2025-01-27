<?php

namespace App\Filament\Resources\DynamicQrCodeResource\Pages;

use App\Filament\Resources\DynamicQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDynamicQrCode extends EditRecord
{
    protected static string $resource = DynamicQrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
