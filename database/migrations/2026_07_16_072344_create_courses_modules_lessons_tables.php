<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id')->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('slug', 255)->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['school_id', 'slug']);
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('order');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->unique(['course_id', 'order']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('module_id')->index();
            $table->string('title', 255);
            $table->longText('content')->nullable();
            $table->string('video_embed_url', 500)->nullable();
            $table->unsignedInteger('order');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
            $table->unique(['module_id', 'order']);
        });

        Schema::create('lesson_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id')->index();
            $table->uuid('user_id')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['lesson_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_user');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('courses');
    }
};
