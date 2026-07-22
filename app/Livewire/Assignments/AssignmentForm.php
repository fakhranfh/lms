<?php

namespace App\Livewire\Assignments;

use App\Models\Assignment;
use App\Models\Lesson;
use App\Services\AssignmentService;
use App\Support\CurrentSchool;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AssignmentForm extends Component
{
    public Lesson $lesson;

    public ?Assignment $assignment = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string')]
    public string $promptQuestion = '';

    #[Validate('required|numeric|min:0|max:100')]
    public float $maxScore = 100;

    #[Validate('nullable|numeric|min:0|max:100')]
    public ?float $passingScore = 60;

    public bool $isPublished = false;

    public bool $allowMultipleSubmissions = false;

    /**
     * @var array<int, array{criterion: string, weight: float|int|string, description: string, max_points: float|int|string}>
     */
    public array $rubricItems = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(CurrentSchool $currentSchool, ?Lesson $lesson = null, ?Assignment $assignment = null): void
    {
        abort_unless(auth()->user()->can('assignments.create') || auth()->user()->can('assignments.edit'), 403);

        $lesson ??= $assignment?->lesson;

        abort_if($lesson === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($lesson->module?->course?->school_id === $schoolId, 403);

        $this->lesson = $lesson;

        if ($assignment) {
            $this->assignment = $assignment;
            $this->title = $assignment->title;
            $this->promptQuestion = $assignment->prompt_question;
            $this->maxScore = (float) $assignment->max_score;
            $this->passingScore = $assignment->passing_score !== null ? (float) $assignment->passing_score : null;
            $this->isPublished = $assignment->is_published;
            $this->allowMultipleSubmissions = $assignment->allow_multiple_submissions;
            $this->rubricItems = $assignment->rubricItems();
        }

        if (empty($this->rubricItems)) {
            $this->addRubricItem();
        }
    }

    public function addRubricItem(): void
    {
        $this->rubricItems[] = [
            'criterion' => '',
            'weight' => 0,
            'description' => '',
            'max_points' => 0,
        ];
    }

    public function removeRubricItem(int $index): void
    {
        unset($this->rubricItems[$index]);
        $this->rubricItems = array_values($this->rubricItems);
    }

    public function getTotalWeightProperty(): float
    {
        return collect($this->rubricItems)->sum(fn (array $item) => (float) ($item['weight'] ?? 0));
    }

    public function save(AssignmentService $assignmentService)
    {
        $this->errorMessage = null;

        $this->validate([
            'title' => 'required|string|max:255',
            'promptQuestion' => 'required|string',
            'maxScore' => 'required|numeric|min:0|max:100',
            'passingScore' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($this->passingScore !== null && $this->passingScore > $this->maxScore) {
            $this->addError('passingScore', 'The passing score must not be greater than the max score.');

            return;
        }

        $hasRubricContent = collect($this->rubricItems)->contains(fn (array $item) => trim((string) ($item['criterion'] ?? '')) !== '');

        if ($hasRubricContent && round($this->totalWeight, 2) !== 100.0) {
            $this->errorMessage = 'Rubric item weights must total 100 (currently '.$this->totalWeight.').';

            return;
        }

        $rubric = $hasRubricContent ? array_values($this->rubricItems) : null;

        $data = [
            'lesson_id' => $this->lesson->id,
            'title' => $this->title,
            'prompt_question' => $this->promptQuestion,
            'rubric' => $rubric,
            'max_score' => $this->maxScore,
            'passing_score' => $this->passingScore,
            'is_published' => $this->isPublished,
            'allow_multiple_submissions' => $this->allowMultipleSubmissions,
        ];

        if ($this->assignment) {
            $assignmentService->update($this->assignment->id, $data);
        } else {
            $this->assignment = $assignmentService->create($data);
        }

        return redirect()->route('lessons.edit', $this->lesson);
    }

    public function publish(AssignmentService $assignmentService): void
    {
        abort_unless(auth()->user()->can('assignments.edit'), 403);
        abort_if($this->assignment === null, 404);

        $this->assignment = $assignmentService->publish($this->assignment->id);
        $this->isPublished = true;
        $this->successMessage = 'Assignment published.';
    }

    public function unpublish(AssignmentService $assignmentService): void
    {
        abort_unless(auth()->user()->can('assignments.edit'), 403);
        abort_if($this->assignment === null, 404);

        $this->assignment = $assignmentService->unpublish($this->assignment->id);
        $this->isPublished = false;
        $this->successMessage = 'Assignment unpublished.';
    }

    #[On('delete-confirmed')]
    public function delete(string $id, AssignmentService $assignmentService)
    {
        abort_unless(auth()->user()->can('assignments.delete'), 403);
        abort_if($this->assignment === null || $this->assignment->id !== $id, 404);

        $lesson = $this->lesson;
        $assignmentService->delete($this->assignment->id);

        return redirect()->route('lessons.edit', $lesson);
    }

    public function render()
    {
        return view('livewire.assignments.assignment-form', [
            'pageTitle' => $this->assignment ? 'Edit Assignment' : 'Create Assignment',
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->assignment ? 'Edit Assignment' : 'Create Assignment'])
            ->section('app-content');
    }
}
