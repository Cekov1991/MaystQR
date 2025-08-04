<?php

namespace App\Http\Controllers;

use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FastlaneController extends Controller
{
    public function __construct(
        private PayPalService $paypalService
    ) {}

    /**
     * Show the Fastlane checkout page
     */
    public function index()
    {
        $clientToken = $this->paypalService->getClientToken();
        $clientId = config('services.paypal.client_id');
        $mode = config('services.paypal.mode');
        $sdkUrl = $mode === 'live'
            ? 'https://www.paypal.com/sdk/js'
            : 'https://www.paypal.com/sdk/js';

        return view('fastlane.checkout', compact('clientToken', 'clientId', 'sdkUrl'));
    }

    /**
     * Get client token for AJAX requests
     */
    public function getClientToken(): JsonResponse
    {
        try {
            $clientToken = $this->paypalService->getClientToken();
            return response()->json(['clientToken' => $clientToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process Fastlane transaction
     */
    public function createTransaction(Request $request): JsonResponse
    {
        try {
            $paymentData = [
                'paymentToken' => $request->input('paymentToken'),
                'amount' => $request->input('amount', '100.00'),
                'currency' => $request->input('currency', 'USD'),
                'description' => $request->input('description', 'Fastlane Purchase'),
            ];

            if ($request->has('shippingAddress')) {
                $paymentData['shippingAddress'] = $request->input('shippingAddress');
            }

            $result = $this->paypalService->createFastlaneOrder($paymentData);

            return response()->json(['result' => $result]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}