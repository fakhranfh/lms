<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Removes the legacy Module/Lesson content structure (and everything
     * hanging off it: assignments, submissions, lesson materials/progress)
     * now that the course restructure's Session replaces it. Course itself
     * is untouched.
     */
    public function up(): void
    {
        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->dropForeign(['answer_file_id']);
        });

        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('lesson_material_user');
        Schema::dropIfExists('lesson_materials');
        Schema::dropIfExists('lesson_user');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('modules');

        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->foreign('answer_file_id')->references('id')->on('media_library_items')->nullOnDelete();
        });

        Permission::where('name', 'like', 'modules.%')
            ->orWhere('name', 'like', 'lessons.%')
            ->orWhere('name', 'like', 'assignments.%')
            ->orWhere('name', 'like', 'submissions.%')
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());
    }

    public function down(): void
    {
        // Irreversible: the legacy Module/Lesson/Assignment/Submission
        // structure is being removed permanently, not just deactivated.
        throw new RuntimeException('This migration is not reversible.');
    }
};
