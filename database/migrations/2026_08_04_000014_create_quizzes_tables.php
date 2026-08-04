<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id')->unique();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->unsignedInteger('total_question')->default(0);
            $table->unsignedInteger('total_attempts')->nullable();
            $table->string('scoring_method', 20)->default('highest');
            $table->unsignedInteger('time_limit_per_attempt')->nullable();
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
        });

        Schema::create('quiz_instructions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('content')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quiz_id')->index();
            $table->text('description');
            $table->decimal('points', 6, 2)->default(0);
            $table->string('question_type', 20);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
        });

        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quiz_question_id')->index();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('quiz_question_id')->references('id')->on('quiz_questions')->cascadeOnDelete();
        });

        Schema::create('assessment_quiz_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_attempt_id')->index();
            $table->uuid('quiz_question_id')->index();
            $table->uuid('selected_option_id')->nullable();
            $table->text('answer_text')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->timestamps();

            $table->foreign('assessment_attempt_id')->references('id')->on('assessment_attempts')->cascadeOnDelete();
            $table->foreign('quiz_question_id')->references('id')->on('quiz_questions')->cascadeOnDelete();
            $table->foreign('selected_option_id')->references('id')->on('quiz_question_options')->nullOnDelete();
            $table->unique(['assessment_attempt_id', 'quiz_question_id'], 'aqa_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_quiz_answers');
        Schema::dropIfExists('quiz_question_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quiz_instructions');
        Schema::dropIfExists('quizzes');
    }
};
