<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE demo_lms_accesses DROP CONSTRAINT IF EXISTS demo_lms_accesses_role_check');
            DB::statement("ALTER TABLE demo_lms_accesses ADD CONSTRAINT demo_lms_accesses_role_check CHECK (role IN ('teacher', 'student', 'school-admin'))");

            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('demo_lms_accesses', function (Blueprint $table) {
                $table->enum('role', ['teacher', 'student', 'school-admin'])->default('teacher')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE demo_lms_accesses MODIFY role ENUM('teacher', 'student', 'school-admin') NOT NULL DEFAULT 'teacher'");
    }

    public function down(): void
    {
        DB::table('demo_lms_accesses')->where('role', 'school-admin')->delete();

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE demo_lms_accesses DROP CONSTRAINT IF EXISTS demo_lms_accesses_role_check');
            DB::statement("ALTER TABLE demo_lms_accesses ADD CONSTRAINT demo_lms_accesses_role_check CHECK (role IN ('teacher', 'student'))");

            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('demo_lms_accesses', function (Blueprint $table) {
                $table->enum('role', ['teacher', 'student'])->default('teacher')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE demo_lms_accesses MODIFY role ENUM('teacher', 'student') NOT NULL DEFAULT 'teacher'");
    }
};
