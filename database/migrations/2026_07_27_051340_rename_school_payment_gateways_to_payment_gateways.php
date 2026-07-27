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
        Schema::rename('school_payment_gateways', 'payment_gateways');

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->renameColumn('school_payment_gateway_id', 'payment_gateway_id');
        });

        Schema::table('payment_gateway_credentials', function (Blueprint $table) {
            $table->renameColumn('school_payment_gateway_id', 'payment_gateway_id');
        });

        Schema::table('payment_webhooks', function (Blueprint $table) {
            $table->renameColumn('school_payment_gateway_id', 'payment_gateway_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_webhooks', function (Blueprint $table) {
            $table->renameColumn('payment_gateway_id', 'school_payment_gateway_id');
        });

        Schema::table('payment_gateway_credentials', function (Blueprint $table) {
            $table->renameColumn('payment_gateway_id', 'school_payment_gateway_id');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->renameColumn('payment_gateway_id', 'school_payment_gateway_id');
        });

        Schema::rename('payment_gateways', 'school_payment_gateways');
    }
};
