<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoStudentEnrollmentSeeder extends Seeder
{
    /**
     * Enroll the demo school's "Demo Student" into every course of that
     * school, and into a group in every course that has groups, so the
     * demo account can see all seeded assessments (including team ones).
     *
     * This mirrors the 2026_08_12_000001 and 2026_08_12_000002 migrations,
     * which only backfill existing installs: on a fresh `migrate:fresh
     * --seed`, migrations run before any course/group exists, so those
     * migrations no-op and this seeder is what actually enrolls the demo
     * student.
     */
    public function run(): void
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

        $this->enrollIntoCourses($school->id, $demoStudent->id);
        $this->joinCourseGroups($school->id, $demoStudent->id);
    }

    private function enrollIntoCourses(string $schoolId, string $demoStudentId): void
    {
        $courseIds = DB::table('courses')->where('school_id', $schoolId)->pluck('id');

        $enrolledCourseIds = DB::table('course_people')
            ->where('user_id', $demoStudentId)
            ->whereIn('course_id', $courseIds)
            ->pluck('course_id');

        $now = now();

        foreach ($courseIds->diff($enrolledCourseIds) as $courseId) {
            DB::table('course_people')->insert([
                'id' => (string) Str::uuid(),
                'course_id' => $courseId,
                'user_id' => $demoStudentId,
                'role_in_course' => 'student',
                'enrolled_at' => $now,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function joinCourseGroups(string $schoolId, string $demoStudentId): void
    {
        $courseIds = DB::table('courses')->where('school_id', $schoolId)->pluck('id');

        $groups = DB::table('groups')
            ->whereIn('course_id', $courseIds)
            ->orderBy('created_at')
            ->get(['id', 'course_id'])
            ->unique('course_id');

        $memberOfGroupIds = DB::table('group_members')
            ->where('user_id', $demoStudentId)
            ->whereIn('group_id', $groups->pluck('id'))
            ->pluck('group_id');

        $now = now();

        foreach ($groups->whereNotIn('id', $memberOfGroupIds) as $group) {
            DB::table('group_members')->insert([
                'id' => (string) Str::uuid(),
                'group_id' => $group->id,
                'user_id' => $demoStudentId,
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
