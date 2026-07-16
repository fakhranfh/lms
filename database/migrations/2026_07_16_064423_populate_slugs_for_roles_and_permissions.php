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
        DB::table('permissions')->whereNull('slug')->update([
            'slug' => DB::raw('LOWER(REPLACE(name, \' \', \'-\'))'),
        ]);

        DB::table('roles')->whereNull('slug')->update([
            'slug' => DB::raw('LOWER(REPLACE(name, \' \', \'-\'))'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->update(['slug' => null]);
        DB::table('roles')->update(['slug' => null]);
    }
};
