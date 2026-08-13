<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local mirror of an AgentaOS subscription.
 *
 * A row is created when a user starts a checkout, and completed in two stages:
 * the webhook tells us the checkout was paid (keyed by `checkout_session_id`),
 * and a follow-up API call fills in `agentaos_subscription_id`, which the
 * webhook payload does not carry. See docs/adr/0002.
 *
 * This table is a record of what the provider believes. It is never the gate —
 * `users.entitled_until` is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('checkout_session_id')->nullable()->unique();
            $table->string('agentaos_subscription_id')->nullable()->unique();

            // Mirrored verbatim from AgentaOS: incomplete, incomplete_expired,
            // trialing, active, past_due, canceled, unpaid, paused.
            $table->string('status')->default('incomplete')->index();

            $table->timestamp('current_period_end')->nullable();
            $table->unsignedInteger('unit_amount_minor')->nullable();
            $table->string('currency', 8)->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
