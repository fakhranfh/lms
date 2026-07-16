<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultRoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();

        foreach ($schools as $school) {
            $this->createDefaultRoles($school->id);
        }
    }

    /**
     * Create default roles for a school.
     */
    private function createDefaultRoles(string $schoolId): void
    {
        // Admin Role - All permissions except billing
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => 'admin']
        );

        $adminPermissions = Permission::where('name', '!=', 'settings.billing')->get();
        $adminRole->syncPermissions($adminPermissions);

        // Instructor Role
        $instructorRole = Role::firstOrCreate(
            ['name' => 'Instructor', 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => 'instructor']
        );

        $instructorPermissions = Permission::whereIn('name', [
            // Course Management
            'courses.create',
            'courses.view',
            'courses.edit',
            'courses.delete',
            // Module Management
            'modules.create',
            'modules.view',
            'modules.edit',
            'modules.delete',
            // Lesson Management
            'lessons.create',
            'lessons.view',
            'lessons.edit',
            'lessons.delete',
            // Assignment Management
            'assignments.create',
            'assignments.view',
            'assignments.edit',
            'assignments.delete',
            // Submission Grading
            'submissions.view',
            'submissions.grade',
            'submissions.override-grade',
            // Analytics
            'analytics.view',
        ])->get();

        $instructorRole->syncPermissions($instructorPermissions);

        // Student Role
        $studentRole = Role::firstOrCreate(
            ['name' => 'Student', 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => 'student']
        );

        $studentPermissions = Permission::whereIn('name', [
            'courses.view',
            'modules.view',
            'lessons.view',
            'assignments.view',
            'submissions.view',
        ])->get();

        $studentRole->syncPermissions($studentPermissions);
    }
}
