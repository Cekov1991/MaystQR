<?php

namespace App\Filament\Resources\StaticQrCodeResource\Pages;

use App\Filament\Resources\StaticQrCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaticQrCode extends CreateRecord
{
    protected static string $resource = StaticQrCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'static';
        $data['content'] = $data['destination_url']; // For static QR codes, content is the same as destination
        return $data;
    }
}
