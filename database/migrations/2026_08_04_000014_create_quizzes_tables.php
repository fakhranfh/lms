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
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_instructions');
        Schema::dropIfExists('quizzes');
    }
};
