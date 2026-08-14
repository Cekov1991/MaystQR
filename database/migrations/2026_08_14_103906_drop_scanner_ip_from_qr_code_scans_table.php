<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stops retaining the IP address of people who scan a QR code.
 *
 * The rows in this table describe strangers. Someone scanned a poster or a menu;
 * they have no account, never saw our Privacy Policy, and have no practical way
 * to know the record exists. We were keeping a full unmasked address for each of
 * them indefinitely, and `ScansRelationManager` exposed it to the code's owner as
 * a searchable column.
 *
 * Nothing needed it. The country comes from Cloudflare's CF-IPCountry header,
 * device, OS and browser are parsed from the user agent, and the redirect route's
 * `throttle:60,1` reads the address on the live request without storing it. The
 * only consumer was the column that displayed it. Unique-visitor counting would
 * be the one plausible use and is not implemented — when it is wanted, a
 * daily-rotating salted hash gives uniqueness without retaining the address.
 *
 * `city` goes with it: declared in the original migration, never written by
 * `QrCodeRedirectController::recordScan()`, never read anywhere. An always-null
 * column purporting to hold a scanner's city is worse than no column, because the
 * next person to find it may start filling it in.
 *
 * `sessions.ip_address` is untouched and stays. That address belongs to the
 * account holder, is genuinely useful for session security, expires with the
 * session, and is disclosed in the Privacy Policy.
 *
 * This deletes production data and `down()` cannot bring it back — it restores
 * the columns empty. That is the intent, not an oversight.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'city']);
        });
    }

    /**
     * Restores the columns with their original definitions. The addresses
     * themselves are gone for good.
     */
    public function down(): void
    {
        Schema::table('qr_code_scans', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('blocked');
            $table->string('city')->nullable()->after('country');
        });
    }
};
