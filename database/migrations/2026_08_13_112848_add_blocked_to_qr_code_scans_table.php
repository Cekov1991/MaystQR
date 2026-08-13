<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records scans that arrived while the owner had no entitlement.
 *
 * These are logged but excluded from `qr_codes.scan_count`: the code did not
 * resolve, so it was not a successful scan. Keeping them makes "you missed N
 * scans while your subscription was inactive" answerable, which is the most
 * concrete reactivation argument available.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->boolean('blocked')->default(false)->after('scanned_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->dropIndex(['blocked']);
            $table->dropColumn('blocked');
        });
    }
};
