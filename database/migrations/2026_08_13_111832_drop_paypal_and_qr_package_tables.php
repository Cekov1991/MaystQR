<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retires the per-QR-code package product and its PayPal integration.
 *
 * Billing moves to an account-level subscription (see docs/adr/0001), so these
 * tables have no successor. The purchase flow was never reachable in production
 * — its routes were commented out — so no purchase history is being discarded.
 *
 * Child table drops first: qr_code_package_purchases holds foreign keys into
 * qr_codes, qr_code_packages and users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qr_code_package_purchases');
        Schema::dropIfExists('qr_code_packages');
        Schema::dropIfExists('payment_methods');
    }

    public function down(): void
    {
        Schema::create('qr_code_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('duration_months');
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('qr_code_package_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qr_code_package_id')->constrained();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 8, 2);
            $table->timestamp('purchased_at');
            $table->timestamp('extended_until');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id')->nullable();
            $table->string('email')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
};
