<?php

namespace App\Services;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersPatchRequest;

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
        //To Do. This is not working.
        $request = new OrdersPatchRequest($orderId);
        $request->body = [
            [
                'op' => 'replace',
                'path' => '/intent',
                'value' => 'AUTHORIZE'
            ],
            [
                'op' => 'replace',
                'path' => '/status',
                'value' => 'VOIDED'
            ]
        ];

        try {
            return $this->client->execute($request)->result;
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }
}

