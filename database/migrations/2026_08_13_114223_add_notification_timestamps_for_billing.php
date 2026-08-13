<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Send-once guards for the billing lifecycle emails.
 *
 * The notifier runs daily and its queries would otherwise match the same
 * account every day. Each column records that a given message has been sent,
 * and is cleared again when the situation it described is resolved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_ending_notified_at')->nullable()->after('static_qr_limit');
            $table->timestamp('access_ended_notified_at')->nullable()->after('trial_ending_notified_at');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('past_due_notified_at')->nullable()->after('cancel_at_period_end');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trial_ending_notified_at', 'access_ended_notified_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('past_due_notified_at');
        });
    }
};
