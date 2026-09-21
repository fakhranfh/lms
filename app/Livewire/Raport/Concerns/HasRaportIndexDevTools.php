<?php

namespace App\Livewire\Raport\Concerns;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Enums\RoleName;
use App\Models\User;
use App\Services\CoursePersonService;
use App\Services\CourseService;
use App\Services\GradebookRandomizerService;
use App\Services\RoleService;
use App\Services\UserService;
use App\Support\CurrentSchool;

/**
 * Dev-only bulk generator for Raport's teacher view: enrolls every student
 * in the school into every course in the school (skipping ones they're
 * already enrolled in), then randomizes gradebook scores for each course via
 * GradebookRandomizerService — so a teacher's Raport roster can be exercised
 * end-to-end without hand-enrolling students or hand-grading assessments.
 */
trait HasRaportIndexDevTools
{
    public bool $isLocalEnv = false;

    public function generateRaportScores(
        CurrentSchool $currentSchool,
        RoleService $roleService,
        UserService $userService,
        CourseService $courseService,
        CoursePersonService $coursePersonService,
        GradebookRandomizerService $gradebookRandomizerService,
    ): void {
        abort_unless(app()->environment('local'), 403);
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        // Enrolling + regrading every course in the school for every student
        // routinely runs past PHP's default 30s request limit once there are
        // more than a handful of courses. This is a dev-only bulk action, so
        // lift the cap for it rather than trying to make bulk grading fast.
        set_time_limit(0);

        $this->successMessage = null;

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

        $courses = $courseService->get(['school_id' => $schoolId]);

        $studentRoleId = $roleService->get(['school_id' => $schoolId, 'name' => RoleName::Student->value])->first()?->id;
        $studentIds = $studentRoleId ? $userService->idsMatching(['role_id' => $studentRoleId]) : [];
        $students = $studentIds !== [] ? User::whereIn('id', $studentIds)->get() : collect();

        if ($courses->isEmpty() || $students->isEmpty()) {
            $this->successMessage = __('No courses or students found in this school to generate raport scores for.');

            return;
        }

        $graderId = auth()->id();
        $enrolledCount = 0;

        foreach ($courses as $course) {
            foreach ($students as $student) {
                if ($coursePersonService->isEnrolledAsStudent($course->id, $student->id)) {
                    continue;
                }

                $coursePersonService->enroll($course->id, $student->id, [
                    'role_in_course' => RoleInCourse::Student,
                    'enrolled_at' => now(),
                    'status' => CourseMembershipStatus::Active,
                ]);
                $enrolledCount++;
            }

            $gradebookRandomizerService->randomizeForCourse($course, $graderId, ensureEveryAssessmentType: true);
        }

        $this->successMessage = __('Enrolled :enrolled student(s) and generated random raport scores across :courses course(s).', [
            'enrolled' => $enrolledCount,
            'courses' => $courses->count(),
        ]);
    }
}
