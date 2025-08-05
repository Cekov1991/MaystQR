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
        Schema::create('qr_code_packages', function (Blueprint $table) {
            $table->id();
            $table->string('paddle_price_id')->nullable();
            $table->string('name'); // e.g., "1 Month", "3 Months", etc.
            $table->integer('duration_months'); // 1, 3, 6, 12
            $table->decimal('price', 8, 2); // Package price
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_code_packages');
    }
};