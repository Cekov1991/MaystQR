<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduces account-level entitlement (see docs/adr/0001 and docs/adr/0002).
 *
 * `entitled_until` is the single gate the scan path reads, denormalised so that
 * resolving /q/{shortUrl} never needs a join.
 *
 * Existing accounts are backfilled with a fresh trial from deploy time rather
 * than one computed from `created_at`, so that nobody's dynamic QR codes go
 * dark the moment this ships.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('email_verified_at');
            $table->timestamp('entitled_until')->nullable()->after('trial_ends_at')->index();
            $table->unsignedTinyInteger('dynamic_qr_limit')->nullable()->after('entitled_until');
            $table->unsignedSmallInteger('static_qr_limit')->nullable()->after('dynamic_qr_limit');
        });

        // Raw query builder rather than the User model: a migration must keep
        // working after the model it once referenced has moved or changed.
        $trialEndsAt = now()->addDays((int) config('subscription.trial_days'));

        DB::table('users')->update([
            'trial_ends_at' => $trialEndsAt,
            'entitled_until' => $trialEndsAt,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['entitled_until']);
            $table->dropColumn([
                'trial_ends_at',
                'entitled_until',
                'dynamic_qr_limit',
                'static_qr_limit',
            ]);
        });
    }
};
