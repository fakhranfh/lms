<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'slug']);
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('slug', 255)->nullable();
            $table->unique(['school_id', 'slug']);
        });
    }
};
