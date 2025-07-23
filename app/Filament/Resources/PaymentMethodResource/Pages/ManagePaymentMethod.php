<?php

namespace App\Filament\Resources\PaymentMethodResource\Pages;

use App\Filament\Resources\PaymentMethodResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ManagePaymentMethod extends Page
{
    protected static string $resource = PaymentMethodResource::class;

    protected static string $view = 'filament.resources.payment-method-resource.pages.manage-payment-method';

    public ?array $data = [];

    public function mount(): void
    {
        $paymentMethod = auth()->user()->getPaymentMethod();
        if ($paymentMethod) {
            $this->form->fill([
                'provider' => $paymentMethod->provider,
                'email' => $paymentMethod->email,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Payment Provider')
                    ->description('Configure your payment method for QR code extension purchases.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('provider')
                                    ->options([
                                        'paypal' => 'PayPal',
                                    ])
                                    ->required()
                                    ->helperText('Currently supporting PayPal payments'),

                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->label('PayPal Email')
                                    ->helperText('The email associated with your PayPal account'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Payment Method')
                ->color('primary')
                ->icon('heroicon-o-credit-card')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        auth()->user()->paymentMethods()->delete(); // Remove existing
        auth()->user()->paymentMethods()->create([
            'provider' => $data['provider'],
            'email' => $data['email'],
        ]);

        Notification::make()
            ->success()
            ->title('Payment method saved successfully')
            ->send();
    }
}
