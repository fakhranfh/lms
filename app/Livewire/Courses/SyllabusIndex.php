<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Enums\SyllabusPolicyScope;
use App\Models\Course;
use App\Services\CoursePersonService;
use App\Services\SyllabusService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Support\Collection;
use Livewire\Component;

class SyllabusIndex extends Component
{
    public Course $course;

    public bool $isStudent = false;

    /**
     * Syllabus is queried lazily via wire:init (loadSyllabus), so the initial
     * page render is a cheap skeleton instead of blocking on the query.
     */
    public bool $syllabusLoaded = false;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('syllabus.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadSyllabus(): void
    {
        $this->syllabusLoaded = true;
    }

    public function render(SyllabusService $syllabusService, CoursePersonService $coursePersonService)
    {
        if (! $this->syllabusLoaded) {
            return view('livewire.courses.syllabus-index-placeholder', [
                'course' => $this->course,
                'isStudent' => $this->isStudent,
                'courseTabs' => CourseTabs::build($this->course, 'syllabus'),
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $syllabus = $syllabusService->findByCourse($this->course->id, [
            'classPolicies',
            'learningOutcomes.rubricKeyIndicators.cells.proficiencyLevel',
            'evaluations.activities.learningOutcomes',
            'rubricProficiencyLevels',
            'materials',
        ]);

        $classPoliciesByScope = collect(SyllabusPolicyScope::cases())->mapWithKeys(
            fn (SyllabusPolicyScope $scope) => [$scope->value => $syllabus?->classPolicies->where('scope', $scope) ?? collect()]
        );

        $evaluationsWithTotals = ($syllabus !== null ? $syllabus->evaluations : collect())->map(fn ($evaluation) => [
            'evaluation' => $evaluation,
            'totalWeight' => (float) $evaluation->activities->sum('weight'),
        ]);

        $viewData = [
            'course' => $this->course,
            'syllabus' => $syllabus,
            'canEdit' => auth()->user()->can('syllabus.edit'),
            'courseTabs' => CourseTabs::build($this->course, 'syllabus'),
            'classPoliciesByScope' => $classPoliciesByScope,
            'evaluationsWithTotals' => $evaluationsWithTotals,
            'submissionPoints' => $this->splitIntoPoints($syllabus?->submission_and_collection),
            'teachingLearningStrategyPoints' => $this->splitIntoPoints($syllabus?->teaching_learning_strategies),
            'textbookPoints' => $this->splitIntoPoints($syllabus?->textbooks),
        ];

        if ($this->isStudent) {
            $viewData['teacher'] = $coursePersonService->teachersForCourse($this->course->id)->first()?->user;
        }

        return view($this->isStudent ? 'livewire.courses.syllabus-index-student' : 'livewire.courses.syllabus-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @return Collection<int, non-empty-string>
     */
    private function splitIntoPoints(?string $text): Collection
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values();
    }
}
