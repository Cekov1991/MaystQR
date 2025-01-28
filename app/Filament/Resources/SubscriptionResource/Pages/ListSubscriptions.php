<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    // Remove the create button as subscriptions are created through the plans page
    protected function getHeaderActions(): array
    {
        return [];
    }
}
