<?php

namespace App\Console\Commands\Groups;

use App\Models\Course;
use App\Models\Group;
use App\Services\CoursePersonService;
use App\Services\CourseService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('groups:auto-assign')]
#[Description('Auto-assign students still without a group into groups once a course reaches H-7 before its first session.')]
class AutoAssignUnassignedStudentsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CourseService $courseService, CoursePersonService $coursePersonService, GroupService $groupService, GroupMemberService $groupMemberService): void
    {
        $courses = $courseService->get([], ['sessions'])
            ->filter(fn (Course $course) => $course->sessions->isNotEmpty());

        foreach ($courses as $course) {
            $firstSessionStart = $course->sessions->min('date_start');

            if ($firstSessionStart === null) {
                continue;
            }

            $firstSessionStart = Carbon::parse($firstSessionStart);

            if (! now()->between($firstSessionStart->copy()->subDays(7), $firstSessionStart)) {
                continue;
            }

            $this->assignForCourse($course, $coursePersonService, $groupService, $groupMemberService);
        }
    }

    private function assignForCourse(Course $course, CoursePersonService $coursePersonService, GroupService $groupService, GroupMemberService $groupMemberService): void
    {
        $students = $coursePersonService->studentsForCourse($course->id);
        $groups = $groupService->forCourse($course->id);
        $assignedUserIds = $groups->flatMap(fn (Group $group) => $group->members->pluck('user_id'))->all();

        $unassignedUserIds = $students
            ->reject(fn ($coursePerson) => in_array($coursePerson->user_id, $assignedUserIds, true))
            ->pluck('user_id')
            ->values();

        if ($unassignedUserIds->isEmpty()) {
            return;
        }

        $groupsWithRoom = $groups
            ->filter(fn (Group $group) => $group->target_size !== null && $group->members->count() < $group->target_size)
            ->values();

        $defaultSize = $groups->max('target_size');
        $nextNumber = $groups->count() + 1;
        $currentGroup = null;

        foreach ($unassignedUserIds as $userId) {
            $target = $groupsWithRoom->first(fn (Group $group) => $group->members->count() < $group->target_size);

            if ($target) {
                $groupMemberService->create([
                    'group_id' => $target->id,
                    'user_id' => $userId,
                    'joined_at' => now(),
                ]);

                $target->load('members');

                continue;
            }

            if ($currentGroup === null || ($defaultSize !== null && $currentGroup->members()->count() >= $defaultSize)) {
                $currentGroup = $groupService->create([
                    'course_id' => $course->id,
                    'name' => "Group {$nextNumber}",
                    'created_by' => null,
                    'target_size' => $defaultSize,
                ]);

                $nextNumber++;
                $groupsWithRoom->push($currentGroup);
            }

            $groupMemberService->create([
                'group_id' => $currentGroup->id,
                'user_id' => $userId,
                'joined_at' => now(),
            ]);

            $currentGroup->load('members');
        }
    }
}
