<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->string('title', 255);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->unique(['course_id', 'order']);
        });

        Schema::create('period_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('period_id')->index();
            $table->uuid('session_id')->index();
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('period_id')->references('id')->on('periods')->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
            $table->unique(['period_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_sessions');
        Schema::dropIfExists('periods');
    }
};
