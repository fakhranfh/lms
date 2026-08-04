<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->uuid('session_id')->nullable()->index();
            $table->string('title', 255)->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('course_sessions')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('forum_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('forum_id')->index();
            $table->uuid('user_id')->index();
            $table->string('title', 255);
            $table->text('description');
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();

            $table->foreign('forum_id')->references('id')->on('forums')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('forum_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('thread_id')->index();
            $table->uuid('user_id')->index();
            $table->text('body');
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->foreign('thread_id')->references('id')->on('forum_threads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('forum_comment_likes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('comment_id')->index();
            $table->uuid('user_id')->index();
            $table->timestamps();

            $table->foreign('comment_id')->references('id')->on('forum_comments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_comment_likes');
        Schema::dropIfExists('forum_comments');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('forums');
    }
};
