<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // 'paypal', 'stripe', etc.
            $table->string('provider_id'); // PayPal subscription ID or Stripe customer ID
            $table->string('email')->nullable(); // Customer's email with the provider
            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable(); // For any additional provider-specific data
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
