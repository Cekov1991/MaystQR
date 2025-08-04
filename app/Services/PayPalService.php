<?php

namespace App\Services;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersPatchRequest;
use Illuminate\Support\Facades\Http;

class PayPalService
{
    private PayPalHttpClient $client;

    public function __construct()
    {
        $environment = config('services.paypal.mode') === 'live'
            ? new ProductionEnvironment(config('services.paypal.client_id'), config('services.paypal.secret'))
            : new SandboxEnvironment(config('services.paypal.client_id'), config('services.paypal.secret'));

        $this->client = new PayPalHttpClient($environment);
    }

    public function createSubscription(float $amount, string $planId): object
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');

        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amount, 2, '.', '')
                ]
            ]],
            'application_context' => [
                'return_url' => route('paypal.success'),
                'cancel_url' => route('paypal.cancel')
            ]
        ];

        try {
            return $this->client->execute($request)->result;
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    public function createPackagePurchase(float $amount, int $purchaseId): object
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');

        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amount, 2, '.', '')
                ],
                'description' => "QR Code Extension Package - Purchase #{$purchaseId}"
            ]],
            'application_context' => [
                'return_url' => route('qr.package.success'),
                'cancel_url' => route('qr.package.cancel')
            ]
        ];

        try {
            return $this->client->execute($request)->result;
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    public function capturePayment(string $orderId): object
    {
        $request = new OrdersCaptureRequest($orderId);

        try {
            return $this->client->execute($request)->result;
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    public function cancel(string $orderId): object
    {
        // For captured orders, we can't cancel through PayPal API
        // Just return a success response since the order is already processed
        return (object) [
            'id' => $orderId,
            'status' => 'CANCELLED',
            'message' => 'Order already captured, cancellation handled in database'
        ];
    }

    /**
     * Generate client token for Fastlane SDK initialization
     */
    public function getClientToken(array $domains = []): string
    {
        $baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $response = Http::withBasicAuth(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        )->asForm()->post($baseUrl . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
            'response_type' => 'client_token',
            'intent' => 'sdk_init',
            'domains[]' => $domains ?: [request()->getHost()],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to generate PayPal client token: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Create order for Fastlane payment
     */
    public function createFastlaneOrder(array $paymentData): object
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');

        $body = [
            'intent' => 'CAPTURE',
            'payment_source' => [
                'card' => [
                    'single_use_token' => $paymentData['paymentToken']['id'],
                ],
            ],
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $paymentData['currency'] ?? 'USD',
                    'value' => $paymentData['amount'],
                ],
                'description' => $paymentData['description'] ?? 'Purchase',
            ]],
        ];

        // Add shipping address if provided
        if (isset($paymentData['shippingAddress'])) {
            $shipping = $paymentData['shippingAddress'];

            $body['purchase_units'][0]['shipping'] = [
                'type' => 'SHIPPING',
                'name' => [
                    'full_name' => $shipping['name']['fullName'] ??
                        ($shipping['name']['firstName'] . ' ' . $shipping['name']['lastName'])
                ],
                'address' => [
                    'address_line_1' => $shipping['address']['addressLine1'] ?? '',
                    'address_line_2' => $shipping['address']['addressLine2'] ?? '',
                    'admin_area_2' => $shipping['address']['adminArea2'] ?? '',
                    'admin_area_1' => $shipping['address']['adminArea1'] ?? '',
                    'postal_code' => $shipping['address']['postalCode'] ?? '',
                    'country_code' => $shipping['address']['countryCode'] ?? '',
                ],
            ];

            if (isset($shipping['companyName'])) {
                $body['purchase_units'][0]['shipping']['company_name'] = $shipping['companyName'];
            }

            if (isset($shipping['phoneNumber'])) {
                $body['purchase_units'][0]['shipping']['phone_number'] = [
                    'country_code' => $shipping['phoneNumber']['countryCode'],
                    'national_number' => $shipping['phoneNumber']['nationalNumber'],
                ];
            }
        }

        $request->body = $body;

        try {
            return $this->client->execute($request)->result;
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }
}

