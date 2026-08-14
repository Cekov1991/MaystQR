<?php

namespace Tests\Feature;

use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The pages a stranger lands on after scanning a code. They are the most
 * public thing we ship and the least watched, so every content type is
 * rendered here, including the ones nobody creates often.
 */
class ScanPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        Http::preventStrayRequests();
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function contentTypeProvider(): array
    {
        return [
            'wifi' => ['wifi', ['ssid' => 'Cafe Guest', 'security' => 'WPA2', 'password' => 'hunter2'], 'Cafe Guest'],
            'email' => ['email', ['email' => 'hello@example.com', 'subject' => 'Table for two'], 'hello@example.com'],
            'whatsapp' => ['whatsapp', ['phone' => '38970123456', 'message' => 'Hi there'], '38970123456'],
            'vcard' => ['vcard', ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'], 'Ada Lovelace'],
            'sms' => ['sms', ['phone' => '38970123456', 'message' => 'On my way'], 'On my way'],
            'phone' => ['phone', ['phone' => '38970123456'], '38970123456'],
            'text' => ['text', ['text' => 'Table 12'], 'Table 12'],
            'calendar' => ['calendar', ['summary' => 'Launch party', 'start_date' => '2026-08-15 18:00:00', 'end_date' => '2026-08-15 21:00:00'], 'Launch party'],
            'location' => ['location', ['latitude' => '41.9981', 'longitude' => '21.4254'], '41.9981'],
        ];
    }

    /**
     * @param  array<string, mixed>  $contentData
     */
    #[DataProvider('contentTypeProvider')]
    public function test_each_content_type_renders_when_scanned(string $contentType, array $contentData, string $expected): void
    {
        $qrCode = QrCode::factory()->dynamic()->create([
            'qr_content_type' => $contentType,
            'qr_content_data' => $contentData,
        ]);

        $this->get("/q/{$qrCode->short_url}")
            ->assertOk()
            ->assertViewIs("qr.{$contentType}")
            ->assertSee($expected);
    }

    /**
     * Scan pages carry a customer's wifi password and personal contact details.
     * They must never be indexed.
     */
    #[DataProvider('contentTypeProvider')]
    public function test_each_content_type_is_hidden_from_search_engines(string $contentType, array $contentData): void
    {
        $qrCode = QrCode::factory()->dynamic()->create([
            'qr_content_type' => $contentType,
            'qr_content_data' => $contentData,
        ]);

        $this->get("/q/{$qrCode->short_url}")
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    /**
     * The old pages injected owner-supplied content straight into a JS string
     * literal, so one apostrophe broke the page for whoever scanned it.
     */
    public function test_an_apostrophe_in_scan_content_does_not_break_the_page(): void
    {
        $qrCode = QrCode::factory()->dynamic()->create([
            'qr_content_type' => 'text',
            'qr_content_data' => ['text' => "It's Kiril's table, don't move it"],
        ]);

        $this->get("/q/{$qrCode->short_url}")
            ->assertOk()
            ->assertSee("It's Kiril's table, don't move it");
    }

    /**
     * A location code used to render a view that did not exist, so scanning
     * one threw View [qr.location] not found.
     */
    public function test_a_location_code_offers_both_map_apps(): void
    {
        $qrCode = QrCode::factory()->dynamic()->create([
            'qr_content_type' => 'location',
            'qr_content_data' => ['latitude' => '41.9981', 'longitude' => '21.4254'],
        ]);

        $this->get("/q/{$qrCode->short_url}")
            ->assertOk()
            ->assertSee('google.com/maps', false)
            ->assertSee('maps.apple.com', false);
    }

    /**
     * Bootstrap and the scraped template bundle are 9.7MB that the scan pages
     * used to pull in on a phone. Nothing public may reference them again.
     */
    #[DataProvider('contentTypeProvider')]
    public function test_no_scan_page_loads_the_old_template_bundle(string $contentType, array $contentData): void
    {
        $qrCode = QrCode::factory()->dynamic()->create([
            'qr_content_type' => $contentType,
            'qr_content_data' => $contentData,
        ]);

        $response = $this->get("/q/{$qrCode->short_url}");

        $response->assertOk();
        $response->assertDontSee('landing/assets/vendor', false);
        $response->assertDontSee('landing/assets/css/main.css', false);
        $response->assertSee('css/site.css', false);
    }
}
