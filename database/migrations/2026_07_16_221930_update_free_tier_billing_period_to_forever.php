<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update free tier (price = 0) billing period to forever
        DB::table('pricing_tiers')
            ->where('price', 0)
            ->update(['billing_period' => 'forever']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert free tier billing period back to monthly
        DB::table('pricing_tiers')
            ->where('price', 0)
            ->update(['billing_period' => 'monthly']);
    }
};
