<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE demo_lms_accesses DROP CONSTRAINT IF EXISTS demo_lms_accesses_role_check');
            DB::statement("ALTER TABLE demo_lms_accesses ADD CONSTRAINT demo_lms_accesses_role_check CHECK (role IN ('instructor', 'student', 'school-admin'))");

            return;
        }

        DB::statement("ALTER TABLE demo_lms_accesses MODIFY role ENUM('instructor', 'student', 'school-admin') NOT NULL DEFAULT 'instructor'");
    }

    public function down(): void
    {
        DB::table('demo_lms_accesses')->where('role', 'school-admin')->delete();

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE demo_lms_accesses DROP CONSTRAINT IF EXISTS demo_lms_accesses_role_check');
            DB::statement("ALTER TABLE demo_lms_accesses ADD CONSTRAINT demo_lms_accesses_role_check CHECK (role IN ('instructor', 'student'))");

            return;
        }

        DB::statement("ALTER TABLE demo_lms_accesses MODIFY role ENUM('instructor', 'student') NOT NULL DEFAULT 'instructor'");
    }
};
