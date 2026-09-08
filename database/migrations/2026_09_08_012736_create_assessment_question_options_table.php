<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_question_id')->index();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('assessment_question_id')->references('id')->on('assessment_questions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_question_options');
    }
};
