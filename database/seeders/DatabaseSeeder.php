<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed pricing tiers first (required for schools)
        $this->call(PricingTierSeeder::class);

        // Root domain school is created via migration (2026_07_17_010711)

        // Seed default roles for schools (permissions are seeded via migration)
        $this->call(DefaultRoleSeeder::class);

        // Seed demo courses
        $this->call(CourseSeeder::class);

        // Seed sessions (with subtopics, media, video conferences) for those courses
        $this->call(SessionSeeder::class);

        // Seed syllabuses (class policies, learning outcomes, evaluations, rubric) for those courses
        $this->call(SyllabusSeeder::class);

        // $this->call(ProductSeeder::class);
    }
}
