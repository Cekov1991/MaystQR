<?php

namespace App\Filament\Public\Resources\QrCodeResource\Pages;

use App\Filament\Public\Resources\QrCodeResource;
use App\Models\QrCode;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class CreateQrCode extends CreateRecord
{
    protected static string $resource = QrCodeResource::class;

    public function getTitle(): string
    {
        return 'Create Your Free QR Code';
    }

    public function getSubheading(): string
    {
        return 'Generate a free QR code in seconds. Create an account to save and manage your QR codes.';
    }

    // Override form actions to only show our custom create button
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
        ];
    }

    // Override the create action to store data and redirect to login
    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Create QR Code & Sign Up')
            ->submit('create')
            ->keyBindings(['mod+s'])
            ->color('primary')
            ->size('lg');
    }

    public function create(bool $another = false): void
    {
        // Get the form data
        $data = $this->form->getState();

        /*
         * `options` carries every appearance choice made on this form — style,
         * colour, size, format, error correction and the centre logo. Omitting
         * it silently rebuilt the code with defaults after registration, so a
         * guest who uploaded a logo got a plain code and an orphaned file.
         */
        Session::put('pending_qr_code', [
            'name' => $data['name'],
            'qr_content_type' => $data['qr_content_type'],
            'qr_content_data' => $data['qr_content_data'] ?? [],
            'type' => $data['type'] ?? 'static',
            'options' => $data['options'] ?? [],
        ]);

        // Store a message for after login
        Session::put('qr_code_creation_message', 'Complete your registration to create your QR code!');

        // Redirect to admin login with registration enabled
        $this->redirect('/admin/register');
    }

    // Disable the default create record functionality
    protected function handleRecordCreation(array $data): Model
    {
        // This won't be called since we override the create method
        return new QrCode;
    }
}
