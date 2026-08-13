<?php

namespace Tests\Feature\Subscription;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScanGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        // The scan path used to call a paid geolocation API. Nothing in it may
        // reach off the server now, so a stray request is a failure.
        Http::preventStrayRequests();
    }

    private function dynamicCodeOwnedBy(User $user): QrCode
    {
        return QrCode::factory()->for($user)->dynamic()->create([
            'qr_content_data' => ['url' => 'https://example.com/menu'],
        ]);
    }

    public function test_an_entitled_owners_dynamic_code_resolves(): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        $this->get("/q/{$qrCode->short_url}")
            ->assertRedirect('https://example.com/menu');

        $this->assertSame(1, $qrCode->fresh()->scan_count);
        $this->assertSame(1, $qrCode->scans()->resolved()->count());
        $this->assertSame(0, $qrCode->scans()->blocked()->count());
    }

    public function test_a_lapsed_owners_dynamic_code_shows_the_inactive_page(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->dynamicCodeOwnedBy($user);

        $this->travel(8)->days();

        $this->get("/q/{$qrCode->short_url}")
            ->assertOk()
            ->assertViewIs('qr.inactive')
            ->assertSee('isn’t active right now', false);
    }

    public function test_a_blocked_scan_is_logged_but_not_counted(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->dynamicCodeOwnedBy($user);

        $this->travel(8)->days();

        $this->get("/q/{$qrCode->short_url}");

        $this->assertSame(0, $qrCode->fresh()->scan_count);
        $this->assertSame(1, $qrCode->scans()->blocked()->count());
        $this->assertSame(0, $qrCode->scans()->resolved()->count());
    }

    public function test_the_inactive_page_leaks_nothing_about_the_owner(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Petrova',
            'email' => 'maria@cafe.example',
        ]);
        $qrCode = $this->dynamicCodeOwnedBy($user);

        $this->travel(8)->days();

        $response = $this->get("/q/{$qrCode->short_url}");

        $response->assertDontSee('maria@cafe.example');
        $response->assertDontSee('Maria Petrova');
        $response->assertDontSee('https://example.com/menu');
        $response->assertDontSee($qrCode->name);
    }

    public function test_the_owner_scanning_their_own_code_gets_a_reactivate_prompt(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->dynamicCodeOwnedBy($user);

        $this->travel(8)->days();

        $this->actingAs($user)
            ->get("/q/{$qrCode->short_url}")
            ->assertOk()
            ->assertSee('Your subscription is inactive')
            ->assertSee('Reactivate my subscription');
    }

    public function test_another_logged_in_user_is_treated_as_a_stranger(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->dynamicCodeOwnedBy($owner);
        $someoneElse = User::factory()->create();

        $this->travel(8)->days();

        $this->actingAs($someoneElse)
            ->get("/q/{$qrCode->short_url}")
            ->assertOk()
            ->assertDontSee('Reactivate my subscription');
    }

    public function test_a_subscribers_dynamic_code_keeps_resolving_past_the_trial(): void
    {
        $user = User::factory()->create();
        $user->entitled_until = now()->addYear();
        $user->save();

        $qrCode = $this->dynamicCodeOwnedBy($user);

        $this->travel(30)->days();

        $this->get("/q/{$qrCode->short_url}")
            ->assertRedirect('https://example.com/menu');
    }

    public function test_codes_beyond_the_quota_still_resolve(): void
    {
        // The cap gates creation, never resolution: an account that ended up
        // over its limit must not have already-printed codes broken.
        $user = User::factory()->create(['dynamic_qr_limit' => 1]);

        QrCode::factory()->count(2)->for($user)->dynamic()->create();
        $sixth = $this->dynamicCodeOwnedBy($user);

        $this->assertFalse($user->quota()->canCreate('dynamic'));

        $this->get("/q/{$sixth->short_url}")
            ->assertRedirect('https://example.com/menu');
    }

    public function test_the_country_is_taken_from_the_cloudflare_header(): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        $this->withHeader('CF-IPCountry', 'mk')
            ->get("/q/{$qrCode->short_url}")
            ->assertRedirect('https://example.com/menu');

        $this->assertSame('MK', $qrCode->scans()->sole()->country);
    }

    #[DataProvider('unusableCountryHeaders')]
    public function test_a_country_that_is_not_a_country_is_stored_as_unknown(?string $header): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        $request = $header === null ? $this : $this->withHeader('CF-IPCountry', $header);

        $request->get("/q/{$qrCode->short_url}")->assertRedirect('https://example.com/menu');

        $this->assertNull($qrCode->scans()->sole()->country);
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function unusableCountryHeaders(): array
    {
        return [
            'absent, as it is locally and for anything not behind Cloudflare' => [null],
            'Cloudflare could not resolve one' => ['XX'],
            'arrived over Tor' => ['T1'],
            'forged by a request that bypassed Cloudflare' => ['Neverland'],
            'empty' => [''],
        ];
    }

    /**
     * A scan writes a row, so an unthrottled scan URL is a way to fill the
     * table. The ceiling has to stay clear of what one genuine scanner does.
     */
    public function test_looping_the_scan_url_is_throttled(): void
    {
        $qrCode = $this->dynamicCodeOwnedBy(User::factory()->create());

        for ($i = 0; $i < 60; $i++) {
            $this->get("/q/{$qrCode->short_url}")->assertRedirect();
        }

        $this->get("/q/{$qrCode->short_url}")->assertStatus(429);
        $this->assertSame(60, $qrCode->scans()->count());
    }

    public function test_a_code_can_never_outlive_its_owner(): void
    {
        // The entitlement gate asks the owner whether a code resolves, so it
        // relies on there always being one. qr_codes.user_id is NOT NULL with
        // ON DELETE CASCADE, which is what makes that assumption safe.
        $user = User::factory()->create();
        $qrCode = $this->dynamicCodeOwnedBy($user);

        $user->delete();

        $this->assertDatabaseMissing('qr_codes', ['id' => $qrCode->id]);
    }
}
