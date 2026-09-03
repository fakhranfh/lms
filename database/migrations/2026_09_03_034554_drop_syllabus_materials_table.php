<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('syllabus_materials');
    }

    public function down(): void
    {
        Schema::create('syllabus_materials', function (Blueprint $table) {
            $table->id();
            $table->uuid('syllabus_id')->index();
            $table->string('section', 50);
            $table->uuid('media_library_item_id')->index();
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('syllabus_id')->references('id')->on('syllabuses')->cascadeOnDelete();
            $table->foreign('media_library_item_id')->references('id')->on('media_library_items')->cascadeOnDelete();
        });
    }
};
