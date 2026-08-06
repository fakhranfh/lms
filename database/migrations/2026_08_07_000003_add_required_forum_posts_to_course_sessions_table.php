<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('required_forum_posts')->default(2)->after('delivery_mode');
        });
    }

    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropColumn('required_forum_posts');
        });
    }
};
