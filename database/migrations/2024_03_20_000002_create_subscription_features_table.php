<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('feature_key'); // dynamic_qr_codes, monthly_scans, advanced_analytics, custom_branding
            $table->integer('quantity')->nullable(); // For countable features like scans or QR codes
            $table->boolean('enabled')->default(true); // For boolean features like advanced analytics
            $table->decimal('price', 10, 2);
            $table->timestamp('added_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_features');
    }
};
