<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Disambiguate the school-scoped "Admin" role from the platform-wide
     * "Admin" role (school_id null) by renaming it to "School Admin".
     */
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'Admin')
            ->whereNotNull('school_id')
            ->update(['name' => 'School Admin', 'slug' => 'school-admin']);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'School Admin')
            ->whereNotNull('school_id')
            ->update(['name' => 'Admin', 'slug' => 'admin']);
    }
};
