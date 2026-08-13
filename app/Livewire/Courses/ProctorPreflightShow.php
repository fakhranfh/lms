<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\FinalExam;
use App\Services\CoursePersonService;
use App\Services\FinalExamService;
use App\Support\CurrentSchool;
use Livewire\Component;

class ProctorPreflightShow extends Component
{
    public Course $course;

    public Assessment $assessment;

    public FinalExam $finalExam;

    /**
     * Each check is its own full-screen step, walked through in order.
     */
    public string $step = 'speed';

    /**
     * @var array<string, bool>
     */
    public array $checksPassed = [
        'speed' => false,
        'camera' => false,
        'mic' => false,
        'screen' => false,
    ];

    public function mount(
        CurrentSchool $currentSchool,
        CoursePersonService $coursePersonService,
        FinalExamService $finalExamService,
        ?Course $course = null,
        ?Assessment $assessment = null,
    ): void {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);
        abort_unless(auth()->user()->hasRole(RoleName::Student), 403);
        abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);

        $finalExam = $finalExamService->findByAssessment($assessment->id);
        abort_if($finalExam === null, 404);

        if (! in_array($finalExam->exam_type, [FinalExamType::OpenBook, FinalExamType::ClosedBook], true)) {
            $this->redirectRoute('assessments.final-exam.show', $assessment, navigate: true);

            return;
        }

        $this->course = $course;
        $this->assessment = $assessment;
        $this->finalExam = $finalExam;
    }

    public function markCheckPassed(string $check): void
    {
        abort_unless(array_key_exists($check, $this->checksPassed), 404);

        $this->checksPassed[$check] = true;
    }

    public function markCheckFailed(string $check): void
    {
        abort_unless(array_key_exists($check, $this->checksPassed), 404);

        $this->checksPassed[$check] = false;
    }

    public function goToStep(string $step): void
    {
        abort_unless(in_array($step, ['speed', 'camera', 'mic', 'screen', 'ready'], true), 404);

        $this->step = $step;
    }

    public function getAllChecksPassedProperty(): bool
    {
        return ! in_array(false, $this->checksPassed, true);
    }

    public function render()
    {
        return view('livewire.courses.proctor-preflight-show', [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'finalExam' => $this->finalExam,
            'allChecksPassed' => $this->getAllChecksPassedProperty(),
        ])
            ->extends('layouts.app', ['skipTopbar' => true, 'skipSidebar' => true, 'topbarTitle' => $this->assessment->title])
            ->section('app-content');
    }
}
