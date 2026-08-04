<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id')->unique();
            $table->uuid('period_id')->index();
            $table->string('exam_type', 20);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('allow_local_files')->nullable();
            $table->boolean('allow_internet')->nullable();
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('periods')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_exams');
    }
};
