<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('demo_lms_access')) {
            Schema::rename('demo_lms_access', 'demo_lms_accesses');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('demo_lms_accesses')) {
            Schema::rename('demo_lms_accesses', 'demo_lms_access');
        }
    }
};
