<?php

namespace App\Http\Controllers;

use App\Services\PayPalService;
use Illuminate\Http\Request;

class PayPalController extends Controller
{
    public function __construct(
        private PayPalService $paypalService
    ) {}

    public function connect(Request $request)
    {
        // Add PayPal account to user's payment methods
        $request->user()->paymentMethods()->create([
            'provider' => 'paypal',
            'email' => $request->email,
        ]);

        return redirect()->route('filament.admin.resources.payment-methods.index')
            ->with('success', 'PayPal account connected successfully');
    }

    public function success(Request $request)
    {
        try {
            $result = $this->paypalService->capturePayment($request->token);

            // Handle successful payment
            // Update subscription status, etc.

            return redirect()->route('filament.admin.resources.subscriptions.index')
                ->with('success', 'Payment processed successfully');
        } catch (\Exception $e) {
            return redirect()->route('filament.admin.resources.subscriptions.index')
                ->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('filament.admin.resources.subscriptions.index')
            ->with('info', 'Payment cancelled');
    }
}
