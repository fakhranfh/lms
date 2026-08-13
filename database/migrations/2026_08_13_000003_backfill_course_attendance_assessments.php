<?php

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Backfills the auto-provisioned, course-wide Attendance assessment for
     * every existing course that doesn't already have one, so the
     * Assessment page always mirrors the Attendance page's derived data.
     */
    public function up(): void
    {
        $courseIdsWithAttendance = DB::table('assessments')
            ->where('type', AssessmentType::Attendance->value)
            ->pluck('course_id');

        $courseIds = DB::table('courses')
            ->whereNotIn('id', $courseIdsWithAttendance)
            ->pluck('id');

        $now = now();

        foreach ($courseIds as $courseId) {
            DB::table('assessments')->insert([
                'id' => (string) Str::uuid(),
                'course_id' => $courseId,
                'session_id' => null,
                'type' => AssessmentType::Attendance->value,
                'title' => 'Attendance',
                'weight' => AssessmentType::Attendance->defaultWeight(),
                'assigned_to' => AssessmentAssignedTo::Individual->value,
                'start_date' => null,
                'end_date' => null,
                'status' => AssessmentStatus::Published->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty: this is a one-way data backfill.
    }
};
