<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proctor_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_attempt_id')->unique();
            $table->string('status', 20)->default('active');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('risk_score')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_decision', 20)->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('assessment_attempt_id')->references('id')->on('assessment_attempts')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('proctor_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('proctor_session_id')->index();
            $table->string('event_type', 40);
            $table->string('severity', 10);
            $table->dateTime('detected_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('proctor_session_id')->references('id')->on('proctor_sessions')->cascadeOnDelete();
        });

        Schema::create('proctor_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('proctor_session_id')->index();
            $table->string('type', 10);
            $table->dateTime('captured_at');
            $table->string('file_url', 500);
            $table->uuid('triggered_by_event_id')->nullable();
            $table->timestamps();

            $table->foreign('proctor_session_id')->references('id')->on('proctor_sessions')->cascadeOnDelete();
            $table->foreign('triggered_by_event_id')->references('id')->on('proctor_events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proctor_snapshots');
        Schema::dropIfExists('proctor_events');
        Schema::dropIfExists('proctor_sessions');
    }
};
