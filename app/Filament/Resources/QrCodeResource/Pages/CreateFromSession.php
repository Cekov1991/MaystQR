<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Models\QrCode;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateFromSession extends Page
{
    protected static string $resource = QrCodeResource::class;
    protected static string $view = 'filament.pages.blank-page';

    public function mount(): void
    {
        // Check if there's pending QR code data
        if (!Session::has('pending_qr_code')) {
            $this->redirect('/admin/qr-codes');
            return;
        }

        // Create the QR code with session data
        $data = Session::get('pending_qr_code');
        $data['user_id'] = Auth::id();

        $qrCode = QrCode::create($data);

        // Clear session data
        Session::forget(['pending_qr_code', 'qr_code_creation_message']);

        // Show success notification
        Notification::make()
            ->title('QR Code Created Successfully!')
            ->success()
            ->body('Your QR code has been created and saved to your account.')
            ->send();

        // Redirect to view the created QR code
        $this->redirect("/admin/qr-codes/{$qrCode->id}");
    }
}
