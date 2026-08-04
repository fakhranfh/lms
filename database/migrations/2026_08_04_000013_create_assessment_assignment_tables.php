<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id')->index();
            $table->text('description');
            $table->decimal('points', 6, 2)->default(0);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
        });

        Schema::create('assessment_question_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('assessment_question_id')->index();
            $table->uuid('lesson_material_id')->index();
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('assessment_question_id')->references('id')->on('assessment_questions')->cascadeOnDelete();
            $table->foreign('lesson_material_id')->references('id')->on('lesson_materials')->cascadeOnDelete();
        });

        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_attempt_id')->unique();
            $table->text('answer_text')->nullable();
            $table->uuid('answer_file_id')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->timestamps();

            $table->foreign('assessment_attempt_id')->references('id')->on('assessment_attempts')->cascadeOnDelete();
            $table->foreign('answer_file_id')->references('id')->on('lesson_materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
        Schema::dropIfExists('assessment_question_files');
        Schema::dropIfExists('assessment_questions');
    }
};
