<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: Root domain school creation is handled by RootDomainSchoolSeeder
     * This migration exists for version tracking only.
     */
    public function up(): void
    {
        // No-op: school creation is handled by seeder
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: school deletion is handled by seeder
    }
};
