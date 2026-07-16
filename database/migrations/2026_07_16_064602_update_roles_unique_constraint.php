<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change roles table to allow same role name for different schools.
     * Global roles (school_id=null) still have unique names per guard.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['name', 'guard_name', 'school_id'], 'roles_name_guard_school_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_school_unique');
            $table->unique(['name', 'guard_name']);
        });
    }
};
