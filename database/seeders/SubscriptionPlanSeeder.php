<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'price' => 5.00,
            'dynamic_qr_limit' => 5,
            'scans_per_code' => 1000,
            'is_active' => true,
        ]);
    }
}
