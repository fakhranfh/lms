<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_question_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_attempt_id')->index();
            $table->uuid('assessment_question_id')->index();
            $table->uuid('selected_option_id')->nullable();
            $table->text('answer_text')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->timestamps();

            $table->foreign('assessment_attempt_id')->references('id')->on('assessment_attempts')->cascadeOnDelete();
            $table->foreign('assessment_question_id')->references('id')->on('assessment_questions')->cascadeOnDelete();
            $table->foreign('selected_option_id')->references('id')->on('assessment_question_options')->nullOnDelete();
            $table->unique(['assessment_attempt_id', 'assessment_question_id'], 'aqqa_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_question_answers');
    }
};
