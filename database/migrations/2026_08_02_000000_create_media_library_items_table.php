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
        Schema::create('media_library_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id')->index();
            $table->uuid('uploaded_by')->nullable()->index();
            $table->string('type'); // Video, PDF, Document, Audio, Presentation, Image, Interactive, Markdown
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size'); // bytes
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['school_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_library_items');
    }
};
