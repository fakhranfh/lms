<?php

namespace Database\Seeders;

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
        // Create a test school
        $school = School::create([
            'id' => Str::uuid(),
            'name' => 'Test School',
            'domain' => 'test.local',
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

        // $this->call(ProductSeeder::class);
        $this->call(PaymentGatewayTypeSeeder::class);
        $this->call(PricingTierSeeder::class);
    }
}
