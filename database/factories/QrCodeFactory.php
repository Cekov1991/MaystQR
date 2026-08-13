<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    protected $model = QrCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'type' => 'static',
            'qr_content_type' => 'website',
            'qr_content_data' => ['url' => 'https://'.fake()->domainName()],
            'user_id' => User::factory(),
        ];
    }

    public function dynamic(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'dynamic',
        ]);
    }
}
