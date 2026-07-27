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
            $table->dropForeign(['school_id']);
            $table->dropForeign(['subscription_id']);
            $table->dropColumn(['school_id', 'subscription_id', 'metadata']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->uuid('school_id')->nullable()->after('initiated_by');
            $table->uuid('subscription_id')->nullable()->after('school_id');
            $table->json('metadata')->nullable()->after('currency');

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('id')->on('school_tiers')->cascadeOnDelete();
        });
    }
};
