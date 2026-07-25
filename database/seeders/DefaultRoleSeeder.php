<?php

namespace Database\Seeders;

use App\Models\School;
use App\Services\RoleService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultRoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(RoleService $roleService): void
    {
        School::all()->each(
            fn (School $school) => $roleService->createDefaultRolesForSchool($school->id)
        );
    }
}
