<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymous counts of how often parts of the site are used.
 *
 * A row here records that something happened, never who it happened to. There
 * is deliberately no user id, no session id, no IP address and no user agent —
 * App\Enums\TrackedEvent explains why that boundary is the point of this table,
 * and EventPrivacyTest is the guard that keeps it.
 *
 * No softDeletes(). The scans table declared them while its model never used the
 * trait, which left `scans:prune` looking optional; a retention window published
 * in the Privacy Policy is only true while the deletes are real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_events', function (Blueprint $table) {
            $table->id();

            /*
             * The TrackedEvent case, stored by value. A string rather than a
             * foreign key so that adding an event is one enum case and no
             * migration, and so a rolled-back deploy leaves readable rows
             * instead of dangling ids.
             */
            $table->string('name');

            /*
             * Which arm of an experiment, or which surface raised the event.
             * Low cardinality by contract: an allowlisted label, never free
             * text from a request.
             */
            $table->string('variant')->nullable();

            /*
             * One or two low-cardinality facts about the event, for the
             * questions a name alone cannot answer ("png or svg?"). Never a
             * URL, never anything a person typed.
             */
            $table->json('context')->nullable();

            $table->timestamp('occurred_at');

            /*
             * Every question asked of this table is "how many of X since when",
             * and the prune deletes by date alone.
             */
            $table->index(['name', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_events');
    }
};
