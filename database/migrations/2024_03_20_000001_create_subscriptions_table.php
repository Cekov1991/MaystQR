<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('paypal_subscription_id')->nullable();
            $table->string('status')->default('active'); // active, cancelled, expired
            $table->integer('dynamic_qr_codes_limit')->default(1);
            $table->integer('monthly_scan_limit')->default(1000);
            $table->boolean('has_advanced_analytics')->default(false);
            $table->boolean('has_custom_branding')->default(false);
            $table->decimal('current_price', 10, 2)->default(0);
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
