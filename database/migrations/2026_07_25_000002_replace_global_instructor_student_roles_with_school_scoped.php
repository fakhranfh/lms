<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Every school gets its own Teacher/Student/School Admin roles so
     * permission changes for one school never affect another. The old
     * global (school_id null) Teacher/Student roles are removed and any
     * users holding them are re-assigned to their school's own role.
     */
    public function up(): void
    {
        $schoolAdminPermissionIds = DB::table('permissions')
            ->where('name', '!=', 'settings.billing')
            ->pluck('id');

        $teacherPermissionIds = DB::table('permissions')
            ->whereIn('name', [
                'courses.create', 'courses.view', 'courses.edit', 'courses.delete',
                'modules.create', 'modules.view', 'modules.edit', 'modules.delete',
                'lessons.create', 'lessons.view', 'lessons.edit', 'lessons.delete',
                'assignments.create', 'assignments.view', 'assignments.edit', 'assignments.delete',
                'submissions.view', 'submissions.grade', 'submissions.override-grade',
                'analytics.view',
            ])
            ->pluck('id');

        $studentPermissionIds = DB::table('permissions')
            ->whereIn('name', [
                'courses.view', 'modules.view', 'lessons.view', 'assignments.view', 'submissions.view',
            ])
            ->pluck('id');

        $roleDefinitions = [
            RoleName::SchoolAdmin->value => ['slug' => RoleName::SchoolAdmin->slug(), 'permissions' => $schoolAdminPermissionIds],
            RoleName::Teacher->value => ['slug' => RoleName::Teacher->slug(), 'permissions' => $teacherPermissionIds],
            RoleName::Student->value => ['slug' => RoleName::Student->slug(), 'permissions' => $studentPermissionIds],
        ];

        $schools = DB::table('schools')->pluck('id');

        $schoolRoleIds = []; // [school_id][role_name] => role_id

        foreach ($schools as $schoolId) {
            foreach ($roleDefinitions as $name => $definition) {
                $roleId = DB::table('roles')
                    ->where('school_id', $schoolId)
                    ->where('name', $name)
                    ->value('id');

                if (! $roleId) {
                    $roleId = DB::table('roles')->insertGetId([
                        'school_id' => $schoolId,
                        'name' => $name,
                        'slug' => $definition['slug'],
                        'guard_name' => 'web',
                        'protected' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($definition['permissions'] as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }

                $schoolRoleIds[$schoolId][$name] = $roleId;
            }
        }

        // Re-assign users holding the old global Teacher/Student roles to their school's role.
        foreach ([RoleName::Teacher->value, RoleName::Student->value] as $roleName) {
            $globalRoleId = DB::table('roles')
                ->whereNull('school_id')
                ->where('name', $roleName)
                ->value('id');

            if (! $globalRoleId) {
                continue;
            }

            $assignments = DB::table('model_has_roles')
                ->where('role_id', $globalRoleId)
                ->where('model_type', User::class)
                ->get();

            foreach ($assignments as $assignment) {
                $userSchoolId = DB::table('users')->where('id', $assignment->model_id)->value('school_id');

                if ($userSchoolId && isset($schoolRoleIds[$userSchoolId][$roleName])) {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $schoolRoleIds[$userSchoolId][$roleName],
                        'model_id' => $assignment->model_id,
                        'model_type' => $assignment->model_type,
                    ]);
                }
            }

            DB::table('model_has_roles')->where('role_id', $globalRoleId)->delete();
            DB::table('role_has_permissions')->where('role_id', $globalRoleId)->delete();
            DB::table('roles')->where('id', $globalRoleId)->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: the global Teacher/Student roles are intentionally
        // not recreated. Re-run `2026_07_17_010105_create_teacher_and_student_roles`
        // manually if the old global roles are needed back.
    }
};
