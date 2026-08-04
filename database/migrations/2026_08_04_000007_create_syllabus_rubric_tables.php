<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_rubric_key_indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learning_outcome_id')->index();
            $table->string('code', 20);
            $table->text('description');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('learning_outcome_id')->references('id')->on('syllabus_learning_outcomes')->cascadeOnDelete();
        });

        Schema::create('syllabus_rubric_proficiency_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('syllabus_id')->index();
            $table->string('label', 50);
            $table->unsignedInteger('score_min');
            $table->unsignedInteger('score_max');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('syllabus_id')->references('id')->on('syllabuses')->cascadeOnDelete();
        });

        Schema::create('syllabus_rubric_cells', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rubric_key_indicator_id')->index();
            $table->uuid('rubric_proficiency_level_id')->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('rubric_key_indicator_id', 'rubric_cell_indicator_fk')
                ->references('id')->on('syllabus_rubric_key_indicators')->cascadeOnDelete();
            $table->foreign('rubric_proficiency_level_id', 'rubric_cell_level_fk')
                ->references('id')->on('syllabus_rubric_proficiency_levels')->cascadeOnDelete();
            $table->unique(['rubric_key_indicator_id', 'rubric_proficiency_level_id'], 'rubric_cell_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_rubric_cells');
        Schema::dropIfExists('syllabus_rubric_proficiency_levels');
        Schema::dropIfExists('syllabus_rubric_key_indicators');
    }
};
