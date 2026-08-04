<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gradebook_grade_scales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->nullable()->index();
            $table->string('label', 10);
            $table->unsignedInteger('score_min');
            $table->unsignedInteger('score_max');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        Schema::create('gradebook_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->uuid('user_id')->index();
            $table->string('assessment_type', 40);
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('score', 6, 2)->nullable();
            $table->dateTime('last_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['course_id', 'user_id', 'assessment_type']);
        });

        Schema::create('gradebook_session_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('gradebook_entry_id')->index();
            $table->uuid('session_id')->index();
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('score', 6, 2)->nullable();
            $table->timestamps();

            $table->foreign('gradebook_entry_id')->references('id')->on('gradebook_entries')->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
            $table->unique(['gradebook_entry_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gradebook_session_entries');
        Schema::dropIfExists('gradebook_entries');
        Schema::dropIfExists('gradebook_grade_scales');
    }
};
