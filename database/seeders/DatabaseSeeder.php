<?php

namespace Database\Seeders;

use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed pricing tiers first (required for schools)
        $this->call(PricingTierSeeder::class);

        // Get the basic tier for the test school
        $basicTier = PricingTier::where('slug', 'basic')->first();

        // Create a test school
        $school = School::create([
            'id' => Str::uuid(),
            'name' => 'Test School',
            'domain' => 'test.local',
            'tier_id' => $basicTier->id,
        ]);

        // Create test user directly (avoid factory UUID generation issue)
        User::create([
            'id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'school_id' => $school->id,
            'email_verified_at' => now(),
            'password' => Hash::make('Password@123123'),
            'timezone' => 'UTC',
        ]);

        // Seed default roles for schools (permissions are seeded via migration)
        $this->call(DefaultRoleSeeder::class);

        // $this->call(ProductSeeder::class);
    }
}
