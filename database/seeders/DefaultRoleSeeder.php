<?php

namespace Database\Seeders;

use App\Enums\RoleName;
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
        // School Admin Role - All permissions except billing, scoped to this school
        $schoolAdminRole = Role::firstOrCreate(
            ['name' => RoleName::SchoolAdmin->value, 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => RoleName::SchoolAdmin->slug()]
        );

        $schoolAdminPermissions = Permission::where('name', '!=', 'settings.billing')->get();
        $schoolAdminRole->syncPermissions($schoolAdminPermissions);

        // Instructor Role - Course/Module/Lesson/Assignment CRUD + grading + analytics
        $instructorRole = Role::firstOrCreate(
            ['name' => RoleName::Instructor->value, 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => RoleName::Instructor->slug()]
        );

        $instructorPermissions = Permission::whereIn('name', RoleName::Instructor->defaultPermissions())->get();

        $instructorRole->syncPermissions($instructorPermissions);

        // Student Role - View only
        $studentRole = Role::firstOrCreate(
            ['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => RoleName::Student->slug()]
        );

        $studentPermissions = Permission::whereIn('name', RoleName::Student->defaultPermissions())->get();

        $studentRole->syncPermissions($studentPermissions);
    }
}
