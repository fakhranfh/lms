<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->index();
            $table->uuid('user_id')->index();
            $table->string('status', 20)->default('absent');
            $table->uuid('recorded_by')->nullable();
            $table->dateTime('recorded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['session_id', 'user_id']);
        });

        Schema::create('attendance_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->string('requirement_type', 30);
            $table->string('label', 255);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        Schema::create('course_attendance_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->unique();
            $table->unsignedInteger('minimal_attendance')->default(0);
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_attendance_settings');
        Schema::dropIfExists('attendance_requirements');
        Schema::dropIfExists('attendances');
    }
};
