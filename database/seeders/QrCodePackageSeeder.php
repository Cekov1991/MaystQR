<?php

namespace Database\Seeders;

use App\Models\QrCodePackage;
use Illuminate\Database\Seeder;

class QrCodePackageSeeder extends Seeder
{
    public function run(): void
    {
        QrCodePackage::create([
            'name' => '1 Month',
            'duration_months' => 1,
            'price' => 2.99,
            'is_active' => true,
        ]);

        QrCodePackage::create([
            'name' => '3 Months',
            'duration_months' => 3,
            'price' => 7.99,
            'is_active' => true,
        ]);

        QrCodePackage::create([
            'name' => '6 Months',
            'duration_months' => 6,
            'price' => 14.99,
            'is_active' => true,
        ]);

        QrCodePackage::create([
            'name' => '12 Months',
            'duration_months' => 12,
            'price' => 24.99,
            'is_active' => true,
        ]);
    }
}
