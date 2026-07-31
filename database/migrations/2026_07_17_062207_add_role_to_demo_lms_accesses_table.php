<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('demo_lms_accesses', function (Blueprint $table) {
            $table->enum('role', ['teacher', 'student'])->default('teacher')->after('access_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demo_lms_accesses', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
