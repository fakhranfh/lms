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
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id')->index();
            $table->string('title', 255);
            $table->longText('prompt_question');
            $table->json('rubric')->nullable();
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('allow_multiple_submissions')->default(false);
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_id')->index();
            $table->uuid('user_id')->index();
            $table->longText('student_answer');
            $table->string('status', 20)->default('pending');
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->json('ai_feedback')->nullable();
            $table->decimal('instructor_score', 5, 2)->nullable();
            $table->longText('instructor_feedback')->nullable();
            $table->timestamp('instructor_reviewed_at')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('graded_at')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['assignment_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
    }
};
