<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Enroll the demo school's "Demo Student" into every course of that
     * school, so the demo account can see all seeded assessments.
     */
    public function up(): void
    {
        $school = DB::table('schools')->where('name', 'School Demo')->first();

        if (! $school) {
            return;
        }

        $demoStudent = DB::table('users')
            ->join('school_user', 'users.id', '=', 'school_user.user_id')
            ->where('school_user.school_id', $school->id)
            ->where('users.name', 'Demo Student')
            ->select('users.id')
            ->first();

        if (! $demoStudent) {
            return;
        }

        $courseIds = DB::table('courses')->where('school_id', $school->id)->pluck('id');

        $enrolledCourseIds = DB::table('course_people')
            ->where('user_id', $demoStudent->id)
            ->whereIn('course_id', $courseIds)
            ->pluck('course_id');

        $now = now();

        foreach ($courseIds->diff($enrolledCourseIds) as $courseId) {
            DB::table('course_people')->insert([
                'id' => (string) Str::uuid(),
                'course_id' => $courseId,
                'user_id' => $demoStudent->id,
                'role_in_course' => 'student',
                'enrolled_at' => $now,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $school = DB::table('schools')->where('name', 'School Demo')->first();

        if (! $school) {
            return;
        }

        $demoStudent = DB::table('users')
            ->join('school_user', 'users.id', '=', 'school_user.user_id')
            ->where('school_user.school_id', $school->id)
            ->where('users.name', 'Demo Student')
            ->select('users.id')
            ->first();

        if (! $demoStudent) {
            return;
        }

        $courseIds = DB::table('courses')->where('school_id', $school->id)->pluck('id');

        DB::table('course_people')
            ->where('user_id', $demoStudent->id)
            ->where('role_in_course', 'student')
            ->whereIn('course_id', $courseIds)
            ->delete();
    }
};
