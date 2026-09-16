<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Course;
use App\Services\CoursePersonService;
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

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    public function render(
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
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

        if ($this->isStudent) {
            $result = $gradebookScoringService->computeForUser($this->course, auth()->id());
            $viewData['result'] = $result;
            $viewData['finalGrade'] = $this->letterGrade($result['final']['score']);
            $viewData['finalLastUpdatedLabel'] = $this->lastUpdatedLabel($result['final']['last_updated_at']);
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
            $viewData['finalGrade'] = null;
            $viewData['finalLastUpdatedLabel'] = null;
            $viewData['typeRows'] = [];

            if ($selectedStudent !== null) {
                $result = $gradebookScoringService->computeForUser($this->course, $selectedStudent->user_id);
                $viewData['result'] = $result;
                $viewData['finalGrade'] = $this->letterGrade($result['final']['score']);
                $viewData['finalLastUpdatedLabel'] = $this->lastUpdatedLabel($result['final']['last_updated_at']);
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
     * @return array<int, array{key: string, label: string, weight: float, score: ?float, last_updated_label: ?string, expandable: bool, sessions_url: ?string}>
     */
    private function typeRows(array $result, ?string $studentId): array
    {
        return collect($result['types'])->map(function (array $typeRow) use ($studentId) {
            $key = $typeRow['type']->value;
            $isSessionBased = in_array($key, ['attendance', 'forum_discussion'], true);
            $expandable = $isSessionBased ? count($typeRow['sessions']) > 0 : true;

            return [
                'key' => $key,
                'label' => $this->typeLabel($key),
                'weight' => $typeRow['weight'],
                'score' => $typeRow['score'],
                'last_updated_label' => $this->lastUpdatedLabel($typeRow['last_updated_at']),
                'expandable' => $expandable,
                'sessions_url' => $expandable
                    ? route('gradebook.sessions', [$this->course, $key]).($studentId ? '?student_id='.$studentId : '')
                    : null,
            ];
        })->all();
    }

    private function typeLabel(string $key): string
    {
        if ($key === 'theory_final_exam') {
            return 'THEORY: FINAL EXAM';
        }

        if (str($key)->startsWith('theory_')) {
            return 'THEORY: '.str($key)->after('theory_')->replace('_', ' ')->title();
        }

        return str($key)->replace('_', ' ')->title()->toString();
    }

    private function lastUpdatedLabel(?Carbon $lastUpdatedAt): ?string
    {
        if ($lastUpdatedAt === null) {
            return null;
        }

        $timezone = auth()->user()->timezone ?: config('app.timezone');
        $viewerDate = $lastUpdatedAt->clone()->setTimezone($timezone);

        $offsetMinutes = $viewerDate->utcOffset();
        $sign = $offsetMinutes < 0 ? '-' : '+';
        $hours = intdiv(abs($offsetMinutes), 60);
        $minutes = abs($offsetMinutes) % 60;
        $gmtOffset = $sign.$hours.($minutes > 0 ? ':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');

        return $viewerDate->translatedFormat('j M Y, H:i').' GMT'.$gmtOffset;
    }

    private function letterGrade(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }
}
