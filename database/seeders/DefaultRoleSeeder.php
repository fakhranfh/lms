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
    }
}
