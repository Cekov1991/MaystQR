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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained();
            $table->string('status')->default('active');
            $table->integer('dynamic_qr_limit')->default(5); // Copied from plan at subscription time
            $table->integer('scans_per_code')->default(1000); // Copied from plan at subscription time
            $table->float('current_price')->default(5.00); // Keeps track of the actual price paid
            $table->timestamp('next_billing_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
