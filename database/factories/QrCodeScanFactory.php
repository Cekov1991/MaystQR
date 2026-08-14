<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\QrCodeScan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrCodeScan>
 */
class QrCodeScanFactory extends Factory
{
    protected $model = QrCodeScan::class;

    /**
     * No IP address, matching what the redirect path actually records — see the
     * docblock on QrCodeRedirectController::recordScan().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'qr_code_id' => QrCode::factory()->dynamic(),
            'scanned_at' => now(),
            'blocked' => false,
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
            'device' => fake()->randomElement(['iPhone', 'AndroidOS', 'Macintosh']),
            'os' => fake()->randomElement(['iOS', 'AndroidOS', 'OS X']),
            'browser' => fake()->randomElement(['Safari', 'Chrome', 'Firefox']),
            'country' => fake()->randomElement(['MK', 'DE', 'NL', 'US']),
        ];
    }

    /**
     * A scan that reached an unentitled owner's code and was turned away. These
     * are still scan records and are covered by the same retention period.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'blocked' => true,
        ]);
    }

    public function scannedMonthsAgo(int $months): static
    {
        return $this->state(fn (array $attributes): array => [
            'scanned_at' => now()->subMonths($months),
        ]);
    }
}
