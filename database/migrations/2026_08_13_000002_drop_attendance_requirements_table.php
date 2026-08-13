<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The configurable attendance-requirement system has been replaced by a
     * fixed rule implemented in AttendanceDerivationService, per the
     * course-restructure Batch 6 revision.
     */
    public function up(): void
    {
        Schema::dropIfExists('attendance_requirements');
    }

    public function down(): void
    {
        Schema::create('attendance_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->string('requirement_type', 30);
            $table->string('label', 255);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });
    }
};
