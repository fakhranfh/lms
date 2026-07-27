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
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->uuid('initiated_by')->nullable()->after('school_id');
            $table->json('registration_data')->nullable()->after('metadata');

            $table->foreign('initiated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->uuid('school_id')->nullable()->change();
            $table->uuid('school_payment_gateway_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['initiated_by']);
            $table->dropColumn(['initiated_by', 'registration_data']);
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->uuid('school_id')->nullable(false)->change();
            $table->uuid('school_payment_gateway_id')->nullable(false)->change();
        });
    }
};
