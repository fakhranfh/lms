<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Livewire\Concerns\WithQuestionValidationAttributes;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Livewire\Courses\Concerns\HasAssessmentFinalExamFormDevTools;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentQuestionOptionService;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentService;
use App\Services\FinalExamService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssessmentFinalExamForm extends Component
{
    use HasAssessmentFinalExamFormDevTools;
    use WithQuestionValidationAttributes;
    use WithRichTextEditor;

    public Course $course;

    public ?Assessment $assessment = null;

    public string $title = '';

    public string $weight = '0';

    public string $startDate = '';

    public string $endDate = '';

    public string $examType = 'closed_book';

    public string $instructions = '';

    public string $status = 'draft';

    /**
     * A question slot is temporarily null between a client-side remove (see
     * resources/js/syllabus-form.js's removeSyllabusRow) and the next
     * pruneRemoved() call. Multiple choice questions store no points (see
     * persist()); options are nested the same "null hole" way.
     *
     * @var array<int, ?array{id: ?string, description: string, questionType: string, points: string, order: int, options: array<int, ?array{id: ?string, label: string, isCorrect: bool, order: int}>}>
     */
    public array $questions = [];

    public function mount(FinalExamService $finalExamService, ?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $course ??= $assessment?->course;

        abort_if($course === null, 404);

        $schoolId = auth()->user()->school_id;
        abort_unless($course->school_id === $schoolId, 403);

        $this->course = $course;

        if ($assessment) {
            abort_unless($assessment->course_id === $course->id, 404);
            abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);

            $this->assessment = $assessment;
            $this->title = $assessment->title;
            $this->weight = (string) $assessment->weight;
            $this->startDate = $assessment->start_date?->format('Y-m-d\TH:i') ?? '';
            $this->endDate = $assessment->end_date?->format('Y-m-d\TH:i') ?? '';
            $this->status = $assessment->status->value;
            $this->questions = $assessment->questions->map(fn ($question) => [
                'id' => $question->id,
                'description' => $question->description,
                'questionType' => $question->question_type->value,
                'points' => $question->question_type === AssessmentQuestionType::Essay ? (string) $question->points : '',
                'order' => $question->order,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'isCorrect' => $option->is_correct,
                    'order' => $option->order,
                ])->all(),
            ])->all();

            $finalExam = $finalExamService->findByAssessment($assessment->id);
            if ($finalExam) {
                $this->examType = $finalExam->exam_type->value;
                $this->instructions = $finalExam->instructions ?? '';
            }
        } else {
            $this->weight = (string) AssessmentType::TheoryFinalExam->defaultWeight();
        }

        if ($this->questions === []) {
            $this->addQuestion();
        }
    }

    protected function richTextAttachmentFolder(): string
    {
        return 'final-exam-questions';
    }

    public function addQuestion(): void
    {
        $this->questions[] = $this->defaultQuestion(count($this->questions) + 1);
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    /**
     * @return array{id: ?string, description: string, questionType: string, points: string, order: int, options: array<int, array{id: ?string, label: string, isCorrect: bool, order: int}>}
     */
    private function defaultQuestion(int $order): array
    {
        return [
            'id' => null,
            'description' => '',
            'questionType' => AssessmentQuestionType::Essay->value,
            'points' => '',
            'order' => $order,
            'options' => [],
        ];
    }

    /**
     * Questions/options removed client-side (see resources/js/syllabus-form.js's
     * removeSyllabusRow, reused here) are left as null holes in their arrays
     * rather than spliced out, since reindexing survivors would desync
     * their already-bound wire:model/index-baked handlers. Prune them here,
     * right before validation and persistence.
     */
    private function pruneRemoved(): void
    {
        $this->questions = array_values(array_filter($this->questions, fn ($question) => $question !== null));

        foreach ($this->questions as $index => $question) {
            $this->questions[$index]['options'] = array_values(array_filter($question['options'], fn ($option) => $option !== null));
        }
    }

    public function save(
        AssessmentService $assessmentService,
        AssessmentQuestionService $assessmentQuestionService,
        AssessmentQuestionOptionService $assessmentQuestionOptionService,
        FinalExamService $finalExamService,
    ): mixed {
        try {
            $result = $this->persist($assessmentService, $assessmentQuestionService, $assessmentQuestionOptionService, $finalExamService);
        } catch (\Throwable $exception) {
            $this->dispatch('assessmentfinalexamform-error');

            throw $exception;
        }

        if ($result === null) {
            $this->dispatch('assessmentfinalexamform-error');
        }

        return $result;
    }

    private function persist(
        AssessmentService $assessmentService,
        AssessmentQuestionService $assessmentQuestionService,
        AssessmentQuestionOptionService $assessmentQuestionOptionService,
        FinalExamService $finalExamService,
    ): mixed {
        $this->pruneRemoved();

        $this->validate([
            'title' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'examType' => 'required|in:open_book,closed_book,take_home',
            'questions' => 'array|min:1',
            'questions.*.description' => 'required|string',
            'questions.*.questionType' => 'required|in:multiple_choice,essay',
            'questions.*.points' => 'required_if:questions.*.questionType,essay|nullable|numeric|min:0',
        ], [], $this->questionValidationAttributes($this->questions, ['description', 'questionType', 'points']));

        if ($this->examType === 'take_home') {
            if (count($this->questions) !== 1 || $this->questions[0]['questionType'] !== AssessmentQuestionType::Essay->value) {
                $this->addError('questions', __('Take-home exams must have exactly one essay question.'));

                return null;
            }
        }

        foreach ($this->questions as $index => $question) {
            if ($question['questionType'] !== AssessmentQuestionType::MultipleChoice->value) {
                continue;
            }

            $labelled = array_filter($question['options'], fn ($option) => trim($option['label']) !== '');
            if (count($labelled) < 2) {
                $this->addError("questions.{$index}.options", __('At least two options are required.'));
            }

            $correctCount = count(array_filter($question['options'], fn ($option) => $option['isCorrect']));
            if ($correctCount !== 1) {
                $this->addError("questions.{$index}.options", __('Exactly one option must be marked correct.'));
            }
        }

        $essayQuestions = array_filter($this->questions, fn ($question) => $question['questionType'] === AssessmentQuestionType::Essay->value);
        if ($essayQuestions !== []) {
            $essayPoints = array_sum(array_map(fn ($question) => (float) $question['points'], $essayQuestions));
            if (abs($essayPoints - 100.0) > 0.001) {
                $this->addError('questions', __('The total points of all essay questions must equal 100.'));
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        DB::transaction(function () use ($assessmentService, $assessmentQuestionService, $assessmentQuestionOptionService, $finalExamService) {
            $data = [
                'course_id' => $this->course->id,
                'type' => AssessmentType::TheoryFinalExam,
                'title' => $this->title,
                'weight' => (float) $this->weight,
                'assigned_to' => AssessmentAssignedTo::Individual,
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

            $finalExamData = [
                'assessment_id' => $assessment->id,
                'exam_type' => FinalExamType::from($this->examType),
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'instructions' => $this->instructions !== ''
                    ? HtmlSanitizer::forum($this->promoteRichTextAttachments($this->instructions))
                    : null,
            ];

            $finalExam = $finalExamService->findByAssessment($assessment->id);
            if ($finalExam) {
                $finalExamService->update($finalExam->id, $finalExamData);
            } else {
                $finalExamService->create($finalExamData);
            }

            foreach ($this->questions as $index => $question) {
                $questionType = AssessmentQuestionType::from($question['questionType']);
                $isMultipleChoice = $questionType === AssessmentQuestionType::MultipleChoice;

                $questionData = [
                    'assessment_id' => $assessment->id,
                    'description' => HtmlSanitizer::forum($this->promoteRichTextAttachments($question['description'])),
                    'points' => $isMultipleChoice ? 1 : (float) $question['points'],
                    'question_type' => $questionType,
                    'order' => $index + 1,
                ];

                if ($question['id']) {
                    $questionModel = $assessmentQuestionService->update($question['id'], $questionData);
                } else {
                    $questionModel = $assessmentQuestionService->create($questionData);
                }

                $existingOptionIds = collect($question['options'])->pluck('id')->filter()->all();
                foreach ($questionModel->options as $existingOption) {
                    if (! in_array($existingOption->id, $existingOptionIds, true)) {
                        $assessmentQuestionOptionService->delete($existingOption->id);
                    }
                }

                if (! $isMultipleChoice) {
                    continue;
                }

                foreach ($question['options'] as $optionIndex => $option) {
                    if (trim($option['label']) === '') {
                        continue;
                    }

                    $optionData = [
                        'assessment_question_id' => $questionModel->id,
                        'label' => $option['label'],
                        'is_correct' => $option['isCorrect'],
                        'order' => $optionIndex + 1,
                    ];

                    if ($option['id']) {
                        $assessmentQuestionOptionService->update($option['id'], $optionData);
                    } else {
                        $assessmentQuestionOptionService->create($optionData);
                    }
                }
            }

            return $assessment;
        });

        return redirect()->route('assessments.index', $this->course);
    }

    public function render()
    {
        return view('livewire.courses.assessment-final-exam-form', [
            'pageTitle' => $this->assessment ? 'Edit Final Exam' : 'Create Final Exam',
            'statuses' => AssessmentStatus::cases(),
            'examTypes' => FinalExamType::cases(),
            'questionTypes' => [AssessmentQuestionType::MultipleChoice, AssessmentQuestionType::Essay],
            'showUrl' => $this->assessment ? route('assessments.final-exam.show', $this->assessment) : null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->assessment ? 'Edit Assessment' : 'Create Assessment'])
            ->section('app-content');
    }
}
