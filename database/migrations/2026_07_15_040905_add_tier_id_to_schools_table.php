<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->unsignedBigInteger('tier_id')->nullable()->after('domain');
            $table->foreign('tier_id')->references('id')->on('pricing_tiers')->restrictOnDelete();
        });

        // Backfill existing schools with Basic tier
        $basicTierId = DB::table('pricing_tiers')->where('slug', 'basic')->value('id');

        if ($basicTierId) {
            DB::table('schools')->update(['tier_id' => $basicTierId]);

            // Make tier_id non-nullable after backfill
            Schema::table('schools', function (Blueprint $table) {
                $table->unsignedBigInteger('tier_id')->nullable(false)->change();
            });

            // Create SchoolTier and TierChange records for existing schools
            $schools = DB::table('schools')->get();

            foreach ($schools as $school) {
                $schoolTierId = Str::uuid();

                DB::table('school_tiers')->insert([
                    'id' => $schoolTierId,
                    'school_id' => $school->id,
                    'tier_id' => $basicTierId,
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => null,
                    'renewal_date' => null,
                    'auto_renew' => true,
                    'payment_method' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create initial TierChange record
                DB::table('tier_changes')->insert([
                    'id' => Str::uuid(),
                    'school_tier_id' => $schoolTierId,
                    'from_tier_id' => null,
                    'to_tier_id' => $basicTierId,
                    'change_type' => 'initial',
                    'changed_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['tier_id']);
            $table->dropColumn('tier_id');
        });
    }
};
