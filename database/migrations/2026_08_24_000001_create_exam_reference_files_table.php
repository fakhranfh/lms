<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_reference_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id')->index();
            $table->uuid('user_id')->index();
            $table->string('type', 20);
            $table->string('title');
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_reference_files');
    }
};
