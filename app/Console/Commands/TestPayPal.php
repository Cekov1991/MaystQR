<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PayPalService;

class TestPayPal extends Command
{
    protected $signature = 'test:paypal';
    protected $description = 'Test PayPal integration';

    public function handle()
    {
        $this->info('Testing PayPal configuration...');
        
        // Check config
        $this->info('Client ID: ' . config('services.paypal.client_id'));
        $this->info('Secret: ' . (config('services.paypal.secret') ? 'SET' : 'NOT SET'));
        $this->info('Mode: ' . config('services.paypal.mode'));
        
        try {
            $paypal = new PayPalService();
            $this->info('PayPal service created successfully');
            
            // Test creating a simple order
            $order = $paypal->createSubscription(5.00, 'test');
            $this->info('Order created successfully!');
            $this->info('Order ID: ' . $order->id);
            $this->info('Checkout URL: ' . $order->links[1]->href);
            
        } catch (\Exception $e) {
            $this->error('PayPal error: ' . $e->getMessage());
            $this->error('Error details: ' . $e->getTraceAsString());
        }
    }
}
