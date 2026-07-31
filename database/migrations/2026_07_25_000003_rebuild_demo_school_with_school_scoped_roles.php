<?php

use App\Enums\RoleName;
use App\Models\DemoLmsAccess;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Rebuild the demo school so its Admin/Teacher/Student roles are
     * scoped to the school itself, not shared globally.
     */
    public function up(): void
    {
        $appUrl = config('app.url') ?? 'http://localhost';
        $rootDomain = parse_url($appUrl)['host'] ?? 'localhost';

        $school = School::where('domain', $rootDomain)->where('name', 'School Demo')->first();

        if ($school) {
            DemoLmsAccess::where('school_id', $school->id)->delete();
            User::where('school_id', $school->id)->each(function (User $user) {
                $user->roles()->detach();
                $user->delete();
            });
            Role::where('school_id', $school->id)->delete();
            $school->delete();
        }

        $tier = DB::table('pricing_tiers')->where('slug', 'max')->first();

        if (! $tier) {
            return;
        }

        $school = School::create([
            'id' => (string) Str::uuid(),
            'name' => 'School Demo',
            'domain' => $rootDomain,
            'tier_id' => $tier->id,
        ]);

        $schoolAdminRole = $this->createSchoolRole($school->id, RoleName::SchoolAdmin);
        $schoolAdminRole->syncPermissions(Permission::where('name', '!=', 'settings.billing')->get());

        $teacherRole = $this->createSchoolRole($school->id, RoleName::Teacher);
        $teacherRole->syncPermissions(Permission::whereIn('name', RoleName::Teacher->defaultPermissions())->get());

        $studentRole = $this->createSchoolRole($school->id, RoleName::Student);
        $studentRole->syncPermissions(Permission::whereIn('name', RoleName::Student->defaultPermissions())->get());

        $teacherUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Demo Teacher',
            'email' => 'demo-teacher@demo.'.$rootDomain,
            'password' => Hash::make('demo-password'),
            'email_verified_at' => now(),
            'timezone' => 'UTC',
        ]);
        $teacherUser->assignRole($teacherRole);

        $studentUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Demo Student',
            'email' => 'demo-student@demo.'.$rootDomain,
            'password' => Hash::make('demo-password'),
            'email_verified_at' => now(),
            'timezone' => 'UTC',
        ]);
        $studentUser->assignRole($studentRole);

        // Set the school_id column directly, bypassing the User model's
        // school_user pivot write-through since that table does not exist yet
        // at this point in migration history.
        DB::table('users')->whereIn('id', [$teacherUser->id, $studentUser->id])->update(['school_id' => $school->id]);

        DemoLmsAccess::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'access_token' => Str::random(32),
            'role' => 'teacher',
            'expires_at' => now()->addDays(14),
        ]);

        DemoLmsAccess::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'access_token' => Str::random(32),
            'role' => 'student',
            'expires_at' => now()->addDays(14),
        ]);
    }

    public function down(): void
    {
        $appUrl = config('app.url') ?? 'http://localhost';
        $rootDomain = parse_url($appUrl)['host'] ?? 'localhost';

        School::where('domain', $rootDomain)->where('name', 'School Demo')->delete();
    }

    /**
     * Insert a school-scoped role directly, bypassing Spatie's Role::create()
     * which rejects duplicate names even across different schools.
     */
    private function createSchoolRole(string $schoolId, RoleName $roleName): Role
    {
        $id = DB::table('roles')->insertGetId([
            'school_id' => $schoolId,
            'name' => $roleName->value,
            'slug' => $roleName->slug(),
            'guard_name' => 'web',
            'protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Role::findById($id);
    }
};
