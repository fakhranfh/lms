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

        // Seed root domain school with demo credentials
        $this->call(RootDomainSchoolSeeder::class);

        // Get the basic tier for the test school
        $basicTier = PricingTier::where('slug', 'basic')->first();

        // Seed default roles for schools (permissions are seeded via migration)
        $this->call(DefaultRoleSeeder::class);

        // Seed sample content for Content Engine
        $this->call(ContentEngineSeeder::class);

        // $this->call(ProductSeeder::class);
    }
}
