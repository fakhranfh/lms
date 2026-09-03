<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Livewire\Concerns\WithDevMaterialAttachments;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentService;
use App\Services\MediaLibraryService;
use App\Services\R2StorageService;
use App\Services\SessionService;
use App\Support\AssessmentTypeLabel;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Collection;
use Livewire\Component;

class AssessmentForm extends Component
{
    use WithDevMaterialAttachments;
    use WithRichTextEditor;

    public Course $course;

    public ?Assessment $assessment = null;

    public AssessmentType $assessmentType;

    public string $title = '';

    public string $weight = '0';

    public string $startDate = '';

    public string $endDate = '';

    public string $sessionId = '';

    public string $status = 'draft';

    /**
     * A question slot is temporarily null between a client-side remove
     * (see resources/js/syllabus-form.js's removeSyllabusRow, reused here)
     * and the next pruneRemovedQuestions() call.
     *
     * @var array<int, array{id: ?string, description: string, points: string, selectedMaterialIds: array<int, string>, materialSearch: string}|null>
     */
    public array $questions = [];

    public function mount(CurrentSchool $currentSchool, ?Course $course = null, ?Assessment $assessment = null, string $type = ''): void
    {
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $course ??= $assessment?->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($course->school_id === $schoolId, 403);

        $this->course = $course;

        if ($assessment) {
            abort_unless($assessment->course_id === $course->id, 404);
            abort_unless(in_array($assessment->type, [AssessmentType::TheoryPersonalAssignment, AssessmentType::TheoryTeamAssignment, AssessmentType::Attendance, AssessmentType::ForumDiscussion], true), 404);

            $this->assessment = $assessment;
            $this->assessmentType = $assessment->type;
            $this->title = $assessment->title;
            $this->weight = (string) $assessment->weight;
            $this->startDate = $assessment->start_date?->format('Y-m-d\TH:i') ?? '';
            $this->endDate = $assessment->end_date?->format('Y-m-d\TH:i') ?? '';
            $this->sessionId = $assessment->session_id ?? '';
            $this->status = $assessment->status->value;
            $this->questions = $assessment->questions->map(fn ($question) => [
                'id' => $question->id,
                'description' => $question->description,
                'points' => (string) $question->points,
                'selectedMaterialIds' => $question->files->pluck('id')->all(),
                'materialSearch' => '',
            ])->all();
        } else {
            $this->assessmentType = match ($type) {
                'personal' => AssessmentType::TheoryPersonalAssignment,
                'team' => AssessmentType::TheoryTeamAssignment,
                default => abort(404),
            };
            $this->weight = (string) $this->assessmentType->defaultWeight();
        }

        if ($this->questions === [] && ! in_array($this->assessmentType, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true)) {
            $this->addQuestion();
        }
    }

    public function usesQuestions(): bool
    {
        return ! in_array($this->assessmentType, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true);
    }

    public function addQuestion(): void
    {
        $this->questions[] = [
            'id' => null,
            'description' => '',
            'points' => '',
            'selectedMaterialIds' => [],
            'materialSearch' => '',
        ];
    }

    /**
     * Dev-only: fills the form with fake data so the UI can be exercised
     * without manually typing every field.
     */
    public function devAutofill(MediaLibraryService $mediaLibraryService, R2StorageService $r2StorageService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $this->title = AssessmentTypeLabel::forType($this->assessmentType).' - Week '.random_int(1, 14).' Practice';
        $this->weight = (string) $this->assessmentType->defaultWeight();
        $this->startDate = now()->format('Y-m-d\TH:i');
        $this->endDate = now()->addWeek()->format('Y-m-d\TH:i');
        $this->status = 'draft';

        if (! $this->usesQuestions()) {
            return;
        }

        $materialIds = $this->devMaterialIds($this->course->school_id, $mediaLibraryService, $r2StorageService);

        $questionContent = [
            'Explain the main concept covered in this week\'s lecture and give one real-world example of it in use.',
            'Compare and contrast two approaches discussed in class, and justify which one you would choose for a given scenario.',
            'Given the sample dataset provided in the course materials, describe the steps you would take to solve the problem.',
        ];

        $this->questions = collect($questionContent)->values()->map(fn ($description, $index) => [
            'id' => null,
            'description' => '<p>'.$description.'</p>',
            'points' => (string) (($index + 1) * 10),
            'selectedMaterialIds' => $materialIds,
            'materialSearch' => '',
        ])->all();

        // The question descriptions run wire:ignore, so their DOM is silent
        // to property changes; they only refresh when told to via this
        // browser event.
        foreach ($this->questions as $index => $question) {
            $this->dispatch('rich-text-set-content', id: "question-{$index}", value: $question['description']);
        }
    }

    /**
     * Questions removed client-side (see resources/js/syllabus-form.js's
     * removeSyllabusRow, reused here) are left as null holes in the array
     * rather than spliced out, since reindexing survivors would desync
     * their already-bound wire:model/index-baked handlers. Prune them here,
     * right before validation and persistence.
     */
    private function pruneRemovedQuestions(): void
    {
        $this->questions = array_values(array_filter($this->questions, fn ($question) => $question !== null));
    }

    public function save(AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService): mixed
    {
        try {
            return $this->persist($assessmentService, $assessmentQuestionService);
        } catch (\Throwable $exception) {
            $this->dispatch('assessmentform-error');

            throw $exception;
        }
    }

    private function persist(AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService): mixed
    {
        $this->pruneRemovedQuestions();

        $this->validate([
            'title' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'sessionId' => 'nullable|string',
            ...($this->usesQuestions() ? [
                'questions' => 'array|min:1',
                'questions.*.description' => 'required|string',
                'questions.*.points' => 'required|numeric|min:0',
            ] : []),
        ]);

        $data = [
            'course_id' => $this->course->id,
            'session_id' => $this->sessionId ?: null,
            'type' => $this->assessmentType,
            'title' => $this->title,
            'weight' => (float) $this->weight,
            'assigned_to' => $this->assessmentType === AssessmentType::TheoryTeamAssignment
                ? AssessmentAssignedTo::Group
                : AssessmentAssignedTo::Individual,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'status' => AssessmentStatus::from($this->status),
        ];

        if ($this->assessment) {
            $assessmentService->update($this->assessment->id, $data);
            $assessment = $this->assessment;

            if ($this->usesQuestions()) {
                $existingIds = collect($this->questions)->pluck('id')->filter()->all();
                foreach ($assessment->questions as $existingQuestion) {
                    if (! in_array($existingQuestion->id, $existingIds, true)) {
                        $assessmentQuestionService->delete($existingQuestion->id);
                    }
                }
            }
        } else {
            $assessment = $assessmentService->create($data);
        }

        if (! $this->usesQuestions()) {
            return redirect()->route('assessments.index', $this->course);
        }

        foreach ($this->questions as $index => $question) {
            $questionData = [
                'assessment_id' => $assessment->id,
                'description' => HtmlSanitizer::forum($this->promoteRichTextAttachments($question['description'])),
                'points' => (float) $question['points'],
                'order' => $index + 1,
            ];

            if ($question['id']) {
                $assessmentQuestionModel = $assessmentQuestionService->update($question['id'], $questionData);
            } else {
                $assessmentQuestionModel = $assessmentQuestionService->create($questionData);
            }

            $materialSync = [];
            foreach (array_values($question['selectedMaterialIds']) as $order => $materialId) {
                $materialSync[$materialId] = ['order' => $order + 1];
            }
            $assessmentQuestionModel->files()->sync($materialSync);
        }

        return redirect()->route('assessments.index', $this->course);
    }

    public function render(MediaLibraryService $mediaLibraryService, SessionService $sessionService)
    {
        $schoolId = $this->course->school_id;

        $mediaByRow = [];

        foreach ($this->questions as $index => $question) {
            if ($question === null) {
                continue;
            }

            $mediaByRow[$index] = Collection::make($mediaLibraryService->list($schoolId, null, $question['materialSearch'] ?: null)->get());
        }

        return view('livewire.courses.assessment-form', [
            'pageTitle' => $this->assessment ? 'Edit '.AssessmentTypeLabel::forType($this->assessmentType) : 'Create '.AssessmentTypeLabel::forType($this->assessmentType),
            'sessions' => $sessionService->forCourse($this->course->id),
            'statuses' => AssessmentStatus::cases(),
            'mediaByRow' => $mediaByRow,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->assessment ? 'Edit Assessment' : 'Create Assessment'])
            ->section('app-content');
    }
}
