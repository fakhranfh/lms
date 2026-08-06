<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->index();
            $table->uuid('user_id')->index();
            $table->unsignedTinyInteger('percent');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['session_id', 'user_id'], 'session_progress_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_progress');
    }
};
