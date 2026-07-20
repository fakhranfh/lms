<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        // Seed default roles for schools (permissions are seeded via migration)
        $this->call(DefaultRoleSeeder::class);

        // Seed sample content for Content Engine
        $this->call(ContentEngineSeeder::class);

        // Seed lesson materials with file uploads to R2 (requires lessons to exist)
        $this->call(DummyMaterialsUploadSeeder::class);

        // $this->call(ProductSeeder::class);
    }
}
