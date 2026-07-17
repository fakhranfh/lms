<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lesson_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id')->index();
            $table->string('type'); // Video, PDF, Document, Audio, Presentation, Image, Interactive
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_url', 500);
            $table->string('file_path', 500)->nullable(); // R2 path for deletion
            $table->unsignedInteger('file_size'); // bytes
            $table->string('mime_type', 100);
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
            $table->unique(['lesson_id', 'order']);
            $table->index(['lesson_id', 'order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_materials');
    }
};
