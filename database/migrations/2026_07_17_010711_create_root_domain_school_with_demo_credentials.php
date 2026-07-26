<?php

use App\Models\DemoLmsAccess;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $appUrl = config('app.url') ?? 'http://localhost';
        $parsedUrl = parse_url($appUrl);
        $rootDomain = $parsedUrl['host'] ?? 'localhost';

        $basicTier = \DB::table('pricing_tiers')->where('slug', 'max')->first();
        if (! $basicTier) {
            return;
        }

        // Create or get root domain school
        $school = School::firstOrCreate(
            ['domain' => $rootDomain],
            [
                'id' => Str::uuid(),
                'name' => 'School Demo',
                'tier_id' => $basicTier->id,
            ]
        );

        // Create demo user
        $demoEmail = 'demo-root@demo.'.$rootDomain;
        $user = User::firstOrCreate(
            ['email' => $demoEmail],
            [
                'id' => Str::uuid(),
                'name' => 'Demo Instructor',
                'password' => Hash::make('demo-password'),
                'email_verified_at' => now(),
                'timezone' => 'UTC',
            ]
        );

        // Set the school_id column directly, bypassing the User model's
        // school_user pivot write-through since that table does not exist yet
        // at this point in migration history.
        DB::table('users')->where('id', $user->id)->update(['school_id' => $school->id]);

        // Create/assign instructor role
        $instructorRole = Role::firstOrCreate(
            ['name' => 'Instructor'],
            ['guard_name' => 'web', 'slug' => 'instructor']
        );

        $user->assignRole($instructorRole);

        // Create demo LMS access token
        DemoLmsAccess::firstOrCreate(
            ['user_id' => $user->id, 'school_id' => $school->id],
            [
                'id' => Str::uuid(),
                'access_token' => Str::random(32),
                'expires_at' => now()->addDays(14),
            ]
        );
    }

    public function down(): void
    {
        $appUrl = config('app.url') ?? 'http://localhost';
        $parsedUrl = parse_url($appUrl);
        $rootDomain = $parsedUrl['host'] ?? 'localhost';

        School::where('domain', $rootDomain)
            ->where('name', 'School Demo')
            ->delete();
    }
};
