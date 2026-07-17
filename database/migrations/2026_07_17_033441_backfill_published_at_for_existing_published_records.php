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
        foreach (['courses', 'modules', 'lessons'] as $table) {
            DB::table($table)
                ->where('is_published', true)
                ->whereNull('published_at')
                ->update(['published_at' => DB::raw('updated_at')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill only — not reversible without losing information
        // about which rows already had a real published_at before this ran.
    }
};
