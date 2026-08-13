<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AttendanceScoringService;
use App\Services\CoursePersonService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class AssessmentAttendanceShow extends Component
{
    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public function mount(
        CurrentSchool $currentSchool,
        CoursePersonService $coursePersonService,
        ?Course $course = null,
        ?Assessment $assessment = null,
    ): void {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::Attendance, 404);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);
        }

        $this->course = $course;
        $this->assessment = $assessment;
    }

    public function render(CoursePersonService $coursePersonService, AttendanceScoringService $attendanceScoringService)
    {
        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'isStudent' => $this->isStudent,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ];

        if ($this->isStudent) {
            $computed = $attendanceScoringService->computeForUser($this->assessment, auth()->id());
            $attendanceScoringService->recomputeForUser($this->assessment, auth()->id());
            $viewData['computed'] = $computed;
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $viewData['studentRows'] = $students->map(function ($coursePerson) use ($attendanceScoringService) {
                $computed = $attendanceScoringService->computeForUser($this->assessment, $coursePerson->user_id);
                $attendanceScoringService->recomputeForUser($this->assessment, $coursePerson->user_id);

                return [
                    'user' => $coursePerson->user,
                    'computed' => $computed,
                ];
            })->values();
        }

        return view('livewire.courses.assessment-attendance-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
