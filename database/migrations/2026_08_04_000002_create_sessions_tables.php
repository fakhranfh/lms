<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->string('title', 255);
            $table->text('learning_outcome')->nullable();
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->string('delivery_mode', 20);
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        Schema::create('session_subtopics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->index();
            $table->string('subtopic', 255);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
            $table->unique(['session_id', 'order']);
        });

        Schema::create('session_materials', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->index();
            $table->uuid('lesson_material_id')->index();
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
            $table->foreign('lesson_material_id')->references('id')->on('lesson_materials')->cascadeOnDelete();
            $table->unique(['session_id', 'lesson_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_materials');
        Schema::dropIfExists('session_subtopics');
        Schema::dropIfExists('course_sessions');
    }
};
