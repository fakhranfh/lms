<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add the demo school's "Demo Student" to a group in every course of
     * that school that already has groups, so the demo account can see
     * team assessments too.
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

        $groups = DB::table('groups')
            ->whereIn('course_id', $courseIds)
            ->orderBy('created_at')
            ->get(['id', 'course_id'])
            ->unique('course_id');

        $memberOfGroupIds = DB::table('group_members')
            ->where('user_id', $demoStudent->id)
            ->whereIn('group_id', $groups->pluck('id'))
            ->pluck('group_id');

        $now = now();

        foreach ($groups->whereNotIn('id', $memberOfGroupIds) as $group) {
            DB::table('group_members')->insert([
                'id' => (string) Str::uuid(),
                'group_id' => $group->id,
                'user_id' => $demoStudent->id,
                'joined_at' => $now,
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
        $groupIds = DB::table('groups')->whereIn('course_id', $courseIds)->pluck('id');

        DB::table('group_members')
            ->where('user_id', $demoStudent->id)
            ->whereIn('group_id', $groupIds)
            ->delete();
    }
};
