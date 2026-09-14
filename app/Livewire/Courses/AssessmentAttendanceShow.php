<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Session;
use App\Services\AttendanceDerivationService;
use App\Services\AttendanceScoringService;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
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

    public function render(
        CoursePersonService $coursePersonService,
        AttendanceScoringService $attendanceScoringService,
        AttendanceDerivationService $attendanceDerivationService,
        GradebookScoringService $gradebookScoringService,
    ) {
        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'isStudent' => $this->isStudent,
            'canEdit' => auth()->user()->can('assessment.edit'),
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ];

        $virtualClassSessions = $attendanceDerivationService->sessionsForCourse($this->course)
            ->filter(fn (Session $session) => in_array($session->delivery_mode, [DeliveryMode::VirtualClass, DeliveryMode::Offline], true))
            ->values();

        if ($this->isStudent) {
            $attendanceScoringService->recomputeForUser($this->assessment, auth()->id());
            $gradebookScoringService->recomputeForUser($this->course, auth()->id());

            $viewData['sessionRows'] = $virtualClassSessions->map(fn (Session $session) => [
                'session' => $session,
                'attended' => $attendanceDerivationService->isSessionAttended($session, auth()->id()),
            ]);
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            foreach ($students as $coursePerson) {
                $attendanceScoringService->recomputeForUser($this->assessment, $coursePerson->user_id);
                $gradebookScoringService->recomputeForUser($this->course, $coursePerson->user_id);
            }

            $viewData['sessionRows'] = $virtualClassSessions->map(function (Session $session) use ($students, $attendanceDerivationService) {
                $attendedCount = $students->filter(
                    fn ($coursePerson) => $attendanceDerivationService->isSessionAttended($session, $coursePerson->user_id)
                )->count();

                return [
                    'session' => $session,
                    'attendedCount' => $attendedCount,
                    'totalStudents' => $students->count(),
                ];
            });
        }

        return view('livewire.courses.assessment-attendance-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
