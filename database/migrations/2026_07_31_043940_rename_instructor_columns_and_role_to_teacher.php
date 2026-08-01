<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fixes up databases where earlier migrations already ran with the old
     * "instructor" naming: renames submissions.instructor_* columns to
     * teacher_*, and renames any existing "Instructor" role rows to "Teacher".
     * No-op on fresh installs, since the schema/seed migrations now create
     * teacher_* columns and "Teacher" roles directly.
     */
    public function up(): void
    {
        if (Schema::hasColumn('submissions', 'instructor_score')) {
            Schema::table('submissions', function (Blueprint $table): void {
                $table->renameColumn('instructor_score', 'teacher_score');
                $table->renameColumn('instructor_feedback', 'teacher_feedback');
                $table->renameColumn('instructor_reviewed_at', 'teacher_reviewed_at');
            });
        }

        DB::table('roles')->where('name', 'Instructor')->update(['name' => 'Teacher', 'slug' => 'teacher']);

        if (Schema::hasTable('demo_lms_accesses')) {
            DB::table('demo_lms_accesses')->where('role', 'instructor')->update(['role' => 'teacher']);
            $this->rewriteDemoLmsAccessesRoleConstraint(['teacher', 'student', 'school-admin']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('submissions', 'teacher_score')) {
            Schema::table('submissions', function (Blueprint $table): void {
                $table->renameColumn('teacher_score', 'instructor_score');
                $table->renameColumn('teacher_feedback', 'instructor_feedback');
                $table->renameColumn('teacher_reviewed_at', 'instructor_reviewed_at');
            });
        }

        DB::table('roles')->where('name', 'Teacher')->update(['name' => 'Instructor', 'slug' => 'instructor']);

        if (Schema::hasTable('demo_lms_accesses')) {
            DB::table('demo_lms_accesses')->where('role', 'teacher')->update(['role' => 'instructor']);
            $this->rewriteDemoLmsAccessesRoleConstraint(['instructor', 'student', 'school-admin']);
        }
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function rewriteDemoLmsAccessesRoleConstraint(array $roles): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $list = implode(', ', array_map(fn (string $role): string => "'{$role}'", $roles));

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE demo_lms_accesses DROP CONSTRAINT IF EXISTS demo_lms_accesses_role_check');
            DB::statement("ALTER TABLE demo_lms_accesses ADD CONSTRAINT demo_lms_accesses_role_check CHECK (role IN ({$list}))");

            return;
        }

        if ($driver === 'sqlite') {
            Schema::table('demo_lms_accesses', function (Blueprint $table) use ($roles): void {
                $table->enum('role', $roles)->default($roles[0])->change();
            });

            return;
        }

        DB::statement("ALTER TABLE demo_lms_accesses MODIFY role ENUM({$list}) NOT NULL DEFAULT '{$roles[0]}'");
    }
};
