<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->unique();
            $table->text('course_description')->nullable();
            $table->text('submission_and_collection')->nullable();
            $table->text('tutorial_activity_plan')->nullable();
            $table->text('teaching_learning_strategies')->nullable();
            $table->text('textbooks')->nullable();
            $table->text('competency_map')->nullable();
            $table->text('video_overview')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        Schema::create('syllabus_class_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('syllabus_id')->index();
            $table->string('scope', 20);
            $table->text('content');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('syllabus_id')->references('id')->on('syllabuses')->cascadeOnDelete();
        });

        Schema::create('syllabus_learning_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('syllabus_id')->index();
            $table->string('code', 20);
            $table->text('description');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('syllabus_id')->references('id')->on('syllabuses')->cascadeOnDelete();
            $table->unique(['syllabus_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_learning_outcomes');
        Schema::dropIfExists('syllabus_class_policies');
        Schema::dropIfExists('syllabuses');
    }
};
