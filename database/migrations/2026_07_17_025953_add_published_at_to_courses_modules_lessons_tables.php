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
        Schema::table('courses', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_published');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_published');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
