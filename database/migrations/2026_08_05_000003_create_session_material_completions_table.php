<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_material_completions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->index();
            $table->uuid('media_library_item_id')->index();
            $table->uuid('user_id')->index();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('course_sessions')->cascadeOnDelete();
            $table->foreign('media_library_item_id')->references('id')->on('media_library_items')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['session_id', 'media_library_item_id', 'user_id'], 'session_material_completions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_material_completions');
    }
};
