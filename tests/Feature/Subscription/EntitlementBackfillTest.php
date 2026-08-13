<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The launch-day guarantee: accounts that already existed before entitlement
 * was introduced get a fresh trial from deploy time, not one computed from
 * their registration date. Nobody's dynamic QR codes go dark on deploy.
 */
class EntitlementBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_existing_users_are_backfilled_with_a_fresh_trial(): void
    {
        // Rewind the schema to its pre-migration shape. The index goes first:
        // SQLite refuses to drop a column an index still references.
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['entitled_until']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'trial_ends_at',
                'entitled_until',
                'dynamic_qr_limit',
                'static_qr_limit',
            ]);
        });

        // An account that registered long before subscriptions existed.
        DB::table('users')->insert([
            'name' => 'Long-standing user',
            'email' => 'early@example.com',
            'password' => Hash::make('password'),
            'created_at' => now()->subYear(),
            'updated_at' => now()->subYear(),
        ]);

        $migration = require database_path(
            'migrations/2026_08_13_112328_add_entitlement_columns_to_users_table.php'
        );

        $migration->up();

        $user = User::where('email', 'early@example.com')->firstOrFail();

        $this->assertTrue($user->isEntitled());
        $this->assertTrue($user->isTrialing());
        $this->assertEqualsWithDelta(
            now()->addDays(7)->timestamp,
            $user->entitled_until->timestamp,
            5,
        );
    }
}
