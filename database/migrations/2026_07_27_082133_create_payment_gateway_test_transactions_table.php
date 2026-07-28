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
        Schema::create('payment_gateway_test_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('transaction_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_test_transactions');
    }
};
