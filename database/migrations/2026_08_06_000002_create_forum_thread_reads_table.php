<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_thread_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('thread_id')->index();
            $table->uuid('user_id')->index();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->foreign('thread_id')->references('id')->on('forum_threads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['thread_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_thread_reads');
    }
};
