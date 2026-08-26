<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Course;
use App\Services\CoursePersonService;
use App\Services\GradebookGradeScaleService;
use App\Services\GradebookScoringService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Carbon\Carbon;
use Livewire\Component;

class GradebookIndex extends Component
{
    public Course $course;

    public bool $isStudent = false;

    public bool $dataLoaded = false;

    public ?string $selectedStudentId = null;

    public bool $showGradeScales = false;

    public string $scaleLabel = '';

    public string $scaleMin = '';

    public string $scaleMax = '';

    public ?string $editingScaleId = null;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('gradebook.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function selectStudent(string $userId): void
    {
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $this->selectedStudentId = $userId;
    }

    public function startEditScale(string $scaleId, GradebookGradeScaleService $gradebookGradeScaleService): void
    {
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $scale = $gradebookGradeScaleService->find($scaleId);

        if (! $scale) {
            return;
        }

        $this->editingScaleId = $scale->id;
        $this->scaleLabel = $scale->label;
        $this->scaleMin = (string) $scale->score_min;
        $this->scaleMax = (string) $scale->score_max;
    }

    public function cancelScaleForm(): void
    {
        $this->editingScaleId = null;
        $this->scaleLabel = '';
        $this->scaleMin = '';
        $this->scaleMax = '';
    }

    public function saveGradeScale(GradebookGradeScaleService $gradebookGradeScaleService): void
    {
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $this->validate([
            'scaleLabel' => 'required|string|max:10',
            'scaleMin' => 'required|integer|min:0|max:100',
            'scaleMax' => 'required|integer|min:0|max:100|gte:scaleMin',
        ]);

        $data = [
            'course_id' => $this->course->id,
            'label' => $this->scaleLabel,
            'score_min' => (int) $this->scaleMin,
            'score_max' => (int) $this->scaleMax,
            'order' => $gradebookGradeScaleService->forCourseOrDefault($this->course->id)->count(),
        ];

        if ($this->editingScaleId) {
            $gradebookGradeScaleService->update($this->editingScaleId, $data);
        } else {
            $gradebookGradeScaleService->create($data);
        }

        $this->cancelScaleForm();
        $this->successMessage = __('Grading scale saved.');
    }

    public function deleteGradeScale(string $scaleId, GradebookGradeScaleService $gradebookGradeScaleService): void
    {
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $gradebookGradeScaleService->delete($scaleId);
        $this->successMessage = __('Grading scale removed.');
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    public function render(
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
        GradebookGradeScaleService $gradebookGradeScaleService,
    ) {
        $viewData = [
            'course' => $this->course,
            'isStudent' => $this->isStudent,
            'canManage' => auth()->user()->can('gradebook.manage'),
            'courseTabs' => CourseTabs::build($this->course, 'gradebook'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.gradebook-index-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $viewData['gradeScales'] = $gradebookGradeScaleService->forCourseOrDefault($this->course->id);

        if ($this->isStudent) {
            $result = $gradebookScoringService->computeForUser($this->course, auth()->id());
            $viewData['result'] = $result;
            $viewData['typeRows'] = $this->typeRows($result, null);
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $viewData['studentRows'] = $students->map(function ($coursePerson) use ($gradebookScoringService) {
                $result = $gradebookScoringService->computeForUser($this->course, $coursePerson->user_id);

                return [
                    'user' => $coursePerson->user,
                    'final' => $result['final'],
                ];
            });

            $selectedStudent = $this->selectedStudentId
                ? $students->first(fn ($coursePerson) => $coursePerson->user_id === $this->selectedStudentId)
                : $students->first();

            $viewData['selectedStudent'] = $selectedStudent?->user;
            $viewData['result'] = null;
            $viewData['typeRows'] = [];

            if ($selectedStudent !== null) {
                $result = $gradebookScoringService->computeForUser($this->course, $selectedStudent->user_id);
                $viewData['result'] = $result;
                $viewData['typeRows'] = $this->typeRows($result, $selectedStudent->user_id);
            }
        }

        return view('livewire.courses.gradebook-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * Builds the blade-ready view model for each Assessment Type row,
     * pre-computing the label/expandability/lazy-load URL server-side so the
     * view only binds variables (per this repo's Blade conventions).
     *
     * @param  array{final: array, types: array<int, array{type: AssessmentType, weight: float, score: ?float, last_updated_at: ?Carbon, sessions: array}>}  $result
     * @return array<int, array{key: string, label: string, weight: float, score: ?float, last_updated_at: ?Carbon, expandable: bool, sessions_url: ?string}>
     */
    private function typeRows(array $result, ?string $studentId): array
    {
        return collect($result['types'])->map(function (array $typeRow) use ($studentId) {
            $key = $typeRow['type']->value;
            $expandable = in_array($key, ['attendance', 'forum_discussion'], true) && count($typeRow['sessions']) > 0;

            return [
                'key' => $key,
                'label' => str($key)->replace('_', ' ')->title()->toString(),
                'weight' => $typeRow['weight'],
                'score' => $typeRow['score'],
                'last_updated_at' => $typeRow['last_updated_at'],
                'expandable' => $expandable,
                'sessions_url' => $expandable
                    ? route('gradebook.sessions', [$this->course, $key]).($studentId ? '?student_id='.$studentId : '')
                    : null,
            ];
        })->all();
    }
}
