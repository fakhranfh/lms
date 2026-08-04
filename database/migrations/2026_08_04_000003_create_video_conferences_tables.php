<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_conferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->index();
            $table->string('title', 255)->nullable();
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            $table->string('meeting_url', 500)->nullable();
            $table->unsignedInteger('required_duration_minutes')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
        });

        Schema::create('video_conference_participations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('video_conference_id')->index();
            $table->uuid('user_id')->index();
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();
            $table->timestamps();

            $table->foreign('video_conference_id')->references('id')->on('video_conferences')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_conference_participations');
        Schema::dropIfExists('video_conferences');
    }
};
