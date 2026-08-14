<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What we record about the people who scan a code.
 *
 * They are strangers: someone scanned a poster or a menu. They have no account,
 * never saw our Privacy Policy, and no practical way to know a record of them
 * exists. Everything here exists to keep that record minimal, because the
 * Privacy Policy now states plainly that we do not store their IP address.
 */
class ScanPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const SCANNER_IP = '203.0.113.9';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        Http::preventStrayRequests();
    }

    private function dynamicCodeOwnedBy(User $user): QrCode
    {
        return QrCode::factory()->for($user)->dynamic()->create([
            'qr_content_data' => ['url' => 'https://example.com/menu'],
        ]);
    }

    private function scanFrom(QrCode $qrCode, string $ip): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get("/q/{$qrCode->short_url}");
    }

    /**
     * Asserts against every stored attribute rather than a named column, so the
     * test still fails if the address is reintroduced under a different name.
     */
    public function test_a_resolved_scan_stores_the_scanner_ip_nowhere(): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        $this->scanFrom($qrCode, self::SCANNER_IP);

        $scan = $qrCode->scans()->sole();

        $this->assertStringNotContainsString(
            self::SCANNER_IP,
            json_encode($scan->getAttributes()),
            'A scan record must not contain the scanner IP address in any column.',
        );
    }

    /**
     * The blocked path calls recordScan() too, and is easy to forget.
     */
    public function test_a_blocked_scan_stores_the_scanner_ip_nowhere(): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        $this->travel(8)->days();

        $this->scanFrom($qrCode, self::SCANNER_IP);

        $scan = $qrCode->scans()->sole();

        $this->assertTrue($scan->blocked);
        $this->assertStringNotContainsString(
            self::SCANNER_IP,
            json_encode($scan->getAttributes()),
        );
    }

    public function test_the_scans_table_has_no_ip_address_column(): void
    {
        $this->assertFalse(Schema::hasColumn('qr_code_scans', 'ip_address'));
    }

    /**
     * Declared in the original migration and never once written to. An
     * always-null column purporting to hold a scanner's city invites someone to
     * start filling it in.
     */
    public function test_the_scans_table_has_no_city_column(): void
    {
        $this->assertFalse(Schema::hasColumn('qr_code_scans', 'city'));
    }

    /**
     * The guard in the other direction: removing the address must not quietly
     * break the analytics the subscription is sold on.
     */
    public function test_a_scan_still_records_the_analytics_we_promise(): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        $this->withServerVariables(['REMOTE_ADDR' => self::SCANNER_IP])
            ->withHeaders([
                'CF-IPCountry' => 'MK',
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
            ])
            ->get("/q/{$qrCode->short_url}");

        $scan = $qrCode->scans()->sole();

        $this->assertSame('MK', $scan->country);
        $this->assertNotNull($scan->browser);
        $this->assertNotNull($scan->os);
        $this->assertNotNull($scan->scanned_at);
        $this->assertSame(1, $qrCode->fresh()->scan_count);
    }

    /**
     * Rate limiting reads the address on the live request. That is fine — it is
     * never written down. Two IPs must still be counted as two scans.
     */
    public function test_scans_from_different_addresses_are_both_recorded(): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        $this->scanFrom($qrCode, '203.0.113.9');
        $this->scanFrom($qrCode, '198.51.100.4');

        $this->assertSame(2, $qrCode->scans()->count());
        $this->assertSame(2, $qrCode->fresh()->scan_count);
    }
}
