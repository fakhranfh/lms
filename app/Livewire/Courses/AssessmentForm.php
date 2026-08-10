<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentService;
use App\Services\MediaLibraryService;
use App\Services\SessionService;
use App\Support\AssessmentTypeLabel;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Collection;
use Livewire\Component;

class AssessmentForm extends Component
{
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
     * @var array<int, array{id: ?string, description: string, points: string, selectedMaterialIds: array<int, string>, materialSearch: string}>
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
            abort_unless(in_array($assessment->type, [AssessmentType::TheoryPersonalAssignment, AssessmentType::TheoryTeamAssignment], true), 404);

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

        if ($this->questions === []) {
            $this->addQuestion();
        }
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

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function toggleQuestionMaterial(int $index, string $materialId): void
    {
        $selected = $this->questions[$index]['selectedMaterialIds'];

        if (in_array($materialId, $selected, true)) {
            $this->questions[$index]['selectedMaterialIds'] = array_values(array_diff($selected, [$materialId]));
        } else {
            $selected[] = $materialId;
            $this->questions[$index]['selectedMaterialIds'] = $selected;
        }
    }

    public function save(AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService): mixed
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'sessionId' => 'nullable|string',
            'questions' => 'array|min:1',
            'questions.*.description' => 'required|string',
            'questions.*.points' => 'required|numeric|min:0',
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

            $existingIds = collect($this->questions)->pluck('id')->filter()->all();
            foreach ($assessment->questions as $existingQuestion) {
                if (! in_array($existingQuestion->id, $existingIds, true)) {
                    $assessmentQuestionService->delete($existingQuestion->id);
                }
            }
        } else {
            $assessment = $assessmentService->create($data);
        }

        foreach ($this->questions as $index => $question) {
            $questionData = [
                'assessment_id' => $assessment->id,
                'description' => HtmlSanitizer::forum($question['description']),
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
        $selectedMediaByRow = [];

        foreach ($this->questions as $index => $question) {
            $mediaByRow[$index] = Collection::make($mediaLibraryService->list($schoolId, null, $question['materialSearch'] ?: null)->get());
            $selectedMediaByRow[$index] = $question['selectedMaterialIds'] === []
                ? Collection::make()
                : MediaLibraryItem::whereIn('id', $question['selectedMaterialIds'])->get();
        }

        return view('livewire.courses.assessment-form', [
            'pageTitle' => $this->assessment ? 'Edit '.AssessmentTypeLabel::forType($this->assessmentType) : 'Create '.AssessmentTypeLabel::forType($this->assessmentType),
            'sessions' => $sessionService->forCourse($this->course->id),
            'statuses' => AssessmentStatus::cases(),
            'mediaByRow' => $mediaByRow,
            'selectedMediaByRow' => $selectedMediaByRow,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->assessment ? 'Edit Assessment' : 'Create Assessment'])
            ->section('app-content');
    }
}
