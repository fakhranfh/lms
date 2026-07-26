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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained('subscription_tiers');
            $table->string('status');
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('renewal_date')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
