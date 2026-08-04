<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('syllabus_id')->index();
            $table->string('class_type', 50);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('syllabus_id')->references('id')->on('syllabuses')->cascadeOnDelete();
        });

        Schema::create('syllabus_evaluation_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('syllabus_evaluation_id')->index();
            $table->string('activity', 255);
            $table->decimal('weight', 5, 2);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('syllabus_evaluation_id')->references('id')->on('syllabus_evaluations')->cascadeOnDelete();
        });

        Schema::create('syllabus_evaluation_activity_learning_outcome', function (Blueprint $table) {
            $table->id();
            $table->uuid('syllabus_evaluation_activity_id')->index('seaLo_activity_id_index');
            $table->uuid('learning_outcome_id')->index('seaLo_outcome_id_index');
            $table->timestamps();

            $table->foreign('syllabus_evaluation_activity_id', 'seaLo_activity_fk')
                ->references('id')->on('syllabus_evaluation_activities')->cascadeOnDelete();
            $table->foreign('learning_outcome_id', 'seaLo_outcome_fk')
                ->references('id')->on('syllabus_learning_outcomes')->cascadeOnDelete();
            $table->unique(['syllabus_evaluation_activity_id', 'learning_outcome_id'], 'seaLo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_evaluation_activity_learning_outcome');
        Schema::dropIfExists('syllabus_evaluation_activities');
        Schema::dropIfExists('syllabus_evaluations');
    }
};
