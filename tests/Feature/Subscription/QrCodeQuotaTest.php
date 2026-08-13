<?php

namespace Tests\Feature\Subscription;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class QrCodeQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    public function test_limits_fall_back_to_config(): void
    {
        $user = User::factory()->create();

        $this->assertSame(5, $user->quota()->limitFor('dynamic'));
        $this->assertSame(50, $user->quota()->limitFor('static'));
    }

    public function test_a_per_user_override_wins_over_config(): void
    {
        $user = User::factory()->create([
            'dynamic_qr_limit' => 25,
            'static_qr_limit' => 200,
        ]);

        $this->assertSame(25, $user->quota()->limitFor('dynamic'));
        $this->assertSame(200, $user->quota()->limitFor('static'));
    }

    public function test_an_unknown_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        User::factory()->create()->quota()->limitFor('holographic');
    }

    public function test_usage_is_counted_per_type(): void
    {
        $user = User::factory()->create();

        QrCode::factory()->count(3)->for($user)->create();
        QrCode::factory()->count(2)->for($user)->dynamic()->create();

        $this->assertSame(3, $user->quota()->usedFor('static'));
        $this->assertSame(2, $user->quota()->usedFor('dynamic'));
    }

    public function test_another_users_codes_do_not_count(): void
    {
        $user = User::factory()->create();
        QrCode::factory()->count(4)->create();

        $this->assertSame(0, $user->quota()->usedFor('static'));
        $this->assertSame(50, $user->quota()->remainingFor('static'));
    }

    public function test_creation_is_blocked_at_the_dynamic_limit(): void
    {
        $user = User::factory()->create();

        QrCode::factory()->count(4)->for($user)->dynamic()->create();
        $this->assertTrue($user->quota()->canCreate('dynamic'));
        $this->assertSame(1, $user->quota()->remainingFor('dynamic'));

        QrCode::factory()->for($user)->dynamic()->create();

        $this->assertFalse($user->quota()->canCreate('dynamic'));
        $this->assertSame(0, $user->quota()->remainingFor('dynamic'));
        $this->assertTrue($user->quota()->hasReachedLimitFor('dynamic'));
    }

    public function test_an_account_over_its_limit_reports_zero_remaining_rather_than_negative(): void
    {
        $user = User::factory()->create(['dynamic_qr_limit' => 2]);

        QrCode::factory()->count(5)->for($user)->dynamic()->create();

        $this->assertSame(0, $user->quota()->remainingFor('dynamic'));
        $this->assertFalse($user->quota()->canCreate('dynamic'));
    }

    public function test_a_lapsed_account_cannot_create_dynamic_codes(): void
    {
        $user = User::factory()->create();

        $this->travel(8)->days();
        $user->refresh();

        $this->assertTrue($user->isLapsed());
        $this->assertFalse($user->quota()->canCreate('dynamic'));
    }

    public function test_a_lapsed_account_can_still_create_static_codes(): void
    {
        $user = User::factory()->create();

        $this->travel(8)->days();
        $user->refresh();

        $this->assertTrue($user->isLapsed());
        $this->assertTrue($user->quota()->canCreate('static'));
        $this->assertSame(50, $user->quota()->remainingFor('static'));
    }

    public function test_a_lapsed_account_is_still_bound_by_the_static_limit(): void
    {
        $user = User::factory()->create(['static_qr_limit' => 2]);

        QrCode::factory()->count(2)->for($user)->create();

        $this->travel(8)->days();
        $user->refresh();

        $this->assertFalse($user->quota()->canCreate('static'));
    }
}
