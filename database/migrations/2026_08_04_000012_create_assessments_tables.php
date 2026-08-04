<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->uuid('session_id')->nullable()->index();
            $table->string('type', 40);
            $table->string('title', 255);
            $table->decimal('weight', 5, 2)->default(0);
            $table->string('assigned_to', 20)->default('individual');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('required_posts_per_session')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('course_sessions')->nullOnDelete();
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->uuid('group_id')->nullable()->index();
            $table->uuid('submitted_by')->nullable();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_attempt_id')->index();
            $table->decimal('score', 6, 2)->nullable();
            $table->uuid('graded_by')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->foreign('assessment_attempt_id')->references('id')->on('assessment_attempts')->cascadeOnDelete();
            $table->foreign('graded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessments');
    }
};
