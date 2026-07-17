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
        // Create Instructor Role
        $instructorRole = DB::table('roles')->insertGetId([
            'name' => 'Instructor',
            'guard_name' => 'web',
            'slug' => 'instructor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get Instructor permissions
        $instructorPermissions = DB::table('permissions')->whereIn('name', [
            'courses.create',
            'courses.view',
            'courses.edit',
            'courses.delete',
            'modules.create',
            'modules.view',
            'modules.edit',
            'modules.delete',
            'lessons.create',
            'lessons.view',
            'lessons.edit',
            'lessons.delete',
            'assignments.create',
            'assignments.view',
            'assignments.edit',
            'assignments.delete',
            'submissions.view',
            'submissions.grade',
            'submissions.override-grade',
            'analytics.view',
        ])->pluck('id')->toArray();

        foreach ($instructorPermissions as $permissionId) {
            DB::table('role_has_permissions')->insert([
                'role_id' => $instructorRole,
                'permission_id' => $permissionId,
            ]);
        }

        // Create Student Role
        $studentRole = DB::table('roles')->insertGetId([
            'name' => 'Student',
            'guard_name' => 'web',
            'slug' => 'student',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get Student permissions
        $studentPermissions = DB::table('permissions')->whereIn('name', [
            'courses.view',
            'modules.view',
            'lessons.view',
            'assignments.view',
            'submissions.view',
        ])->pluck('id')->toArray();

        foreach ($studentPermissions as $permissionId) {
            DB::table('role_has_permissions')->insert([
                'role_id' => $studentRole,
                'permission_id' => $permissionId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('role_has_permissions')
            ->whereIn('role_id', DB::table('roles')->whereIn('name', ['Instructor', 'Student'])->pluck('id'))
            ->delete();

        DB::table('roles')->whereIn('name', ['Instructor', 'Student'])->delete();
    }
};
