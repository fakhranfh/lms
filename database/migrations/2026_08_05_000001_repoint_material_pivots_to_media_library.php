<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * session_materials, syllabus_materials, and assessment_question_files
     * were wired to lesson_materials (the older lesson-scoped Content Engine
     * table). The course restructure docs' "Media Library" actually refers
     * to media_library_items (school-wide). Repoint the FK accordingly.
     */
    public function up(): void
    {
        Schema::table('session_materials', function (Blueprint $table) {
            $table->dropForeign(['lesson_material_id']);
            $table->dropUnique(['session_id', 'lesson_material_id']);
            $table->renameColumn('lesson_material_id', 'media_library_item_id');
        });
        Schema::table('session_materials', function (Blueprint $table) {
            $table->foreign('media_library_item_id')->references('id')->on('media_library_items')->cascadeOnDelete();
            $table->unique(['session_id', 'media_library_item_id']);
        });

        Schema::table('syllabus_materials', function (Blueprint $table) {
            $table->dropForeign(['lesson_material_id']);
            $table->renameColumn('lesson_material_id', 'media_library_item_id');
        });
        Schema::table('syllabus_materials', function (Blueprint $table) {
            $table->foreign('media_library_item_id')->references('id')->on('media_library_items')->cascadeOnDelete();
        });

        Schema::table('assessment_question_files', function (Blueprint $table) {
            $table->dropForeign(['lesson_material_id']);
            $table->renameColumn('lesson_material_id', 'media_library_item_id');
        });
        Schema::table('assessment_question_files', function (Blueprint $table) {
            $table->foreign('media_library_item_id')->references('id')->on('media_library_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_question_files', function (Blueprint $table) {
            $table->dropForeign(['media_library_item_id']);
            $table->renameColumn('media_library_item_id', 'lesson_material_id');
        });
        Schema::table('assessment_question_files', function (Blueprint $table) {
            $table->foreign('lesson_material_id')->references('id')->on('lesson_materials')->cascadeOnDelete();
        });

        Schema::table('syllabus_materials', function (Blueprint $table) {
            $table->dropForeign(['media_library_item_id']);
            $table->renameColumn('media_library_item_id', 'lesson_material_id');
        });
        Schema::table('syllabus_materials', function (Blueprint $table) {
            $table->foreign('lesson_material_id')->references('id')->on('lesson_materials')->cascadeOnDelete();
        });

        Schema::table('session_materials', function (Blueprint $table) {
            $table->dropForeign(['media_library_item_id']);
            $table->renameColumn('media_library_item_id', 'lesson_material_id');
        });
        Schema::table('session_materials', function (Blueprint $table) {
            $table->foreign('lesson_material_id')->references('id')->on('lesson_materials')->cascadeOnDelete();
            $table->unique(['session_id', 'lesson_material_id']);
        });
    }
};
