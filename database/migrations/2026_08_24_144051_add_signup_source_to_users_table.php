<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the account came from, if we know.
 *
 * The one piece of per-person attribution in the funnel, and it lives here rather
 * than on site_events for the reason App\Enums\SignupSource explains: a column on
 * the user's own row is a fact about their account, whereas the same knowledge
 * assembled out of the event table would require an identifier there and would
 * make section 2 of the Privacy Policy false.
 *
 * Nullable, and null is the normal case. Most registrations carry no ref at all,
 * and the column must never become a required field standing between someone and
 * an account. Values are constrained by the enum rather than by the database, so
 * that adding an arm is one case and no migration.
 *
 * Deliberately not in the model's $fillable: it is set from an allowlisted query
 * parameter at registration and must not be settable from posted form input.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signup_source')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signup_source');
        });
    }
};
