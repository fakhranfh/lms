<?php

use App\Models\Forum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Forum::whereNull('session_id')->get()->each->delete();

        Schema::table('forums', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
        });

        Schema::table('forums', function (Blueprint $table) {
            $table->uuid('session_id')->nullable(false)->change();
            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
        });

        Schema::table('forums', function (Blueprint $table) {
            $table->uuid('session_id')->nullable()->change();
            $table->foreign('session_id')->references('id')->on('course_sessions')->nullOnDelete();
        });
    }
};
