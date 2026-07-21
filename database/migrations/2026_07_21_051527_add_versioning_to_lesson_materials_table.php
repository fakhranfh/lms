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
        Schema::table('lesson_materials', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('order');
            $table->boolean('is_active')->default(true)->after('version');

            // Composite unique: each version of a material title in a lesson is unique
            $table->unique(['lesson_id', 'title', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_materials', function (Blueprint $table) {
            $table->dropUnique(['lesson_id', 'title', 'version']);
            $table->dropColumn(['version', 'is_active']);
        });
    }
};
