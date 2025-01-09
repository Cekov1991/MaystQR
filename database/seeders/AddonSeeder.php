<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    public function run(): void
    {
        $addons = [
            [
                'name' => 'Advanced Analytics',
                'type' => 'analytics',
                'key' => 'advanced_analytics',
                'price' => 20.00,
                'features' => [
                    'User demographics',
                    'Geofencing',
                    'Engagement trends',
                    'Custom reports'
                ],
            ],
            [
                'name' => '10,000 Additional Scans',
                'type' => 'scans',
                'key' => 'additional_scans_10k',
                'price' => 10.00,
                'features' => [
                    '10,000 additional monthly scans',
                    'Applies to specific QR code'
                ],
            ],
            [
                'name' => 'Custom Branding',
                'type' => 'customization',
                'key' => 'custom_branding',
                'price' => 5.00,
                'features' => [
                    'Custom colors',
                    'Logo integration',
                    'Advanced templates'
                ],
            ],
        ];

        foreach ($addons as $addon) {
            Addon::create($addon);
        }
    }
}
