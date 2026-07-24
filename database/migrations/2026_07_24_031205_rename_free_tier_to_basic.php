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
        DB::table('pricing_tiers')
            ->where('slug', 'free')
            ->update([
                'slug' => 'basic',
                'name' => 'Basic',
                'billing_period' => 'forever',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pricing_tiers')
            ->where('slug', 'basic')
            ->update([
                'slug' => 'free',
                'name' => 'Free',
                'billing_period' => 'monthly',
            ]);
    }
};
