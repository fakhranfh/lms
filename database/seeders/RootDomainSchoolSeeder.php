<?php

namespace Database\Seeders;

use App\Models\DemoLmsAccess;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RootDomainSchoolSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Extract root domain from APP_URL
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
                'school_id' => $school->id,
                'password' => Hash::make('demo-password'),
                'email_verified_at' => now(),
                'timezone' => 'UTC',
            ]
        );

        // Create/assign instructor role
        $instructorRole = Role::firstOrCreate(
            ['name' => 'Instructor'],
            ['guard_name' => 'web', 'slug' => 'instructor']
        );

        $user->assignRole($instructorRole);

        // Create demo LMS access token
        $demoAccess = DemoLmsAccess::firstOrCreate(
            ['user_id' => $user->id, 'school_id' => $school->id],
            [
                'id' => Str::uuid(),
                'access_token' => Str::random(32),
                'expires_at' => now()->addDays(14),
            ]
        );

        $this->command->info("Root domain school created: {$school->domain}");
        $this->command->info("Demo user: {$demoEmail}");
        $this->command->info('Demo password: demo-password');
    }
}
