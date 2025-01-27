<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('addon_id')->constrained()->onDelete('cascade');
            $table->foreignId('qr_code_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('paypal_subscription_id')->nullable();
            $table->string('status')->default('active'); // active, cancelled, expired
            $table->timestamp('expires_at')->nullable();
            $table->json('settings')->nullable(); // Store addon-specific settings
            $table->timestamps();

            // Ensure user can't purchase same addon multiple times for same QR code
            $table->unique(['user_id', 'addon_id', 'qr_code_id'], 'unique_user_addon_qr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addons');
    }
};
