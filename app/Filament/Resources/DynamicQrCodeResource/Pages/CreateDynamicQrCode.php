<?php

namespace App\Filament\Resources\DynamicQrCodeResource\Pages;

use App\Filament\Resources\DynamicQrCodeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateDynamicQrCode extends CreateRecord
{
    protected static string $resource = DynamicQrCodeResource::class;

    public function mount(): void
    {
        if (!auth()->user()->hasPaymentInformation()) {
            $this->showPayPalWarning();
            return;
        }

        parent::mount();
    }

    protected function showPayPalWarning(): void
    {
        Notification::make()
            ->warning()
            ->title('PayPal Connection Required')
            ->body('To create dynamic QR codes, you need to connect your PayPal account first.')
            ->persistent()
            ->actions([
                Action::make('connect')
                    ->label('Connect PayPal')
                    ->button()
                    ->url(route('filament.admin.pages.payment-information')),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('gray')
                    ->url(route('filament.admin.resources.dynamic-qr-codes.index')),
            ])
            ->send();

        $this->redirect(route('filament.admin.resources.dynamic-qr-codes.index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!auth()->user()->hasPaymentInformation()) {
            $this->showPayPalWarning();
            throw new Halt();
        }

        $data['type'] = 'dynamic';
        return $data;
    }

    protected function afterCreate(): void
    {
        // Update subscription price if not the first dynamic QR code
        $price = auth()->user()->calculateDynamicQrCodePrice();
        if ($price > 0) {
            $subscription = auth()->user()->subscription;
            $subscription->current_price += $price;
            $subscription->save();
        }
    }
}
