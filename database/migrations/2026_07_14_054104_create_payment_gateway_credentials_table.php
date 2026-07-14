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
        Schema::create('payment_gateway_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_payment_gateway_id')->constrained('school_payment_gateways')->cascadeOnDelete();
            $table->string('credential_key');
            $table->text('credential_value');
            $table->boolean('is_sensitive')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_credentials');
    }
};
