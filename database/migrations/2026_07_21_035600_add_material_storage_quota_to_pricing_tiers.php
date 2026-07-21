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
        // Add material_storage_gb limit to all pricing tiers
        $tiers = DB::table('pricing_tiers')->orderBy('id')->get();

        foreach ($tiers as $tier) {
            // Check if limit already exists (idempotent)
            $exists = DB::table('tier_limits')
                ->where('pricing_tier_id', $tier->id)
                ->where('limit_key', 'material_storage_gb')
                ->exists();

            if (! $exists) {
                $quotaGb = match ($tier->slug) {
                    'free' => 1,
                    'plus' => 10,
                    'pro' => 50,
                    'max' => null, // Unlimited
                    default => 1,
                };

                DB::table('tier_limits')->insert([
                    'pricing_tier_id' => $tier->id,
                    'limit_key' => 'material_storage_gb',
                    'limit_value' => $quotaGb,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove material_storage_gb limits from all tiers
        DB::table('tier_limits')
            ->where('limit_key', 'material_storage_gb')
            ->delete();
    }
};
