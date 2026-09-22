<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\QuizScoringMethod;
use App\Livewire\Concerns\WithQuestionValidationAttributes;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Livewire\Courses\Concerns\HasAssessmentQuizFormDevTools;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentQuestionOptionService;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentService;
use App\Services\QuizInstructionService;
use App\Services\QuizService;
use App\Services\SessionService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssessmentQuizForm extends Component
{
    use HasAssessmentQuizFormDevTools;
    use WithQuestionValidationAttributes;
    use WithRichTextEditor;

    public Course $course;

    public ?Assessment $assessment = null;

    public string $title = '';

    public string $weight = '0';

    public string $sessionId = '';

    public string $status = 'draft';

    public string $totalAttempts = '';

    public string $scoringMethod = 'highest';

    public string $timeLimitPerAttempt = '';

    /**
     * A question or option slot is temporarily null between a client-side
     * remove (see resources/js/syllabus-form.js's removeSyllabusRow) and the
     * next pruneRemoved() call. Questions are not individually weighted —
     * points are split evenly across them at save time (see equalPoints()).
     *
     * @var array<int, ?array{id: ?string, description: string, order: int, options: array<int, ?array{id: ?string, label: string, isCorrect: bool, order: int}>}>
     */
    public array $questions = [];

    public function mount(?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $course ??= $assessment?->course;

        abort_if($course === null, 404);

        $schoolId = auth()->user()->school_id;
        abort_unless($course->school_id === $schoolId, 403);

        $this->course = $course;

        if ($assessment) {
            abort_unless($assessment->course_id === $course->id, 404);
            abort_unless($assessment->type === AssessmentType::TheoryQuiz, 404);

            $this->assessment = $assessment;
            $this->title = $assessment->title;
            $this->weight = (string) $assessment->weight;
            $this->sessionId = $assessment->session_id ?? '';
            $this->status = $assessment->status->value;

            $quiz = $assessment->quiz;
            if ($quiz) {
                $this->totalAttempts = $quiz->total_attempts !== null ? (string) $quiz->total_attempts : '';
                $this->scoringMethod = $quiz->scoring_method->value;
                $this->timeLimitPerAttempt = $quiz->time_limit_per_attempt !== null ? (string) $quiz->time_limit_per_attempt : '';

                $this->questions = $quiz->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'description' => $question->description,
                    'order' => $question->order,
                    'options' => $question->options->map(fn ($option) => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'isCorrect' => $option->is_correct,
                        'order' => $option->order,
                    ])->all(),
                ])->all();
            }
        } else {
            $this->weight = (string) AssessmentType::TheoryQuiz->defaultWeight();
            $this->totalAttempts = '3';
        }

        if ($this->questions === []) {
            $this->addQuestion();
        }
    }

    protected function richTextAttachmentFolder(): string
    {
        return 'quiz-questions';
    }

    public function addQuestion(): void
    {
        $this->questions[] = [
            'id' => null,
            'description' => '',
            'order' => count($this->questions) + 1,
            'options' => $this->defaultOptions(),
        ];
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function addOption(int $questionIndex): void
    {
        $options = $this->questions[$questionIndex]['options'];
        $options[] = [
            'id' => null,
            'label' => '',
            'isCorrect' => false,
            'order' => count($options) + 1,
        ];
        $this->questions[$questionIndex]['options'] = $options;
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        unset($this->questions[$questionIndex]['options'][$optionIndex]);
        $this->questions[$questionIndex]['options'] = array_values($this->questions[$questionIndex]['options']);
    }

    public function toggleCorrect(int $questionIndex, int $optionIndex): void
    {
        foreach ($this->questions[$questionIndex]['options'] as $i => $option) {
            $this->questions[$questionIndex]['options'][$i]['isCorrect'] = $i === $optionIndex;
        }
    }

    /**
     * @return array<int, array{id: ?string, label: string, isCorrect: bool, order: int}>
     */
    private function defaultOptions(): array
    {
        return [
            ['id' => null, 'label' => '', 'isCorrect' => false, 'order' => 1],
            ['id' => null, 'label' => '', 'isCorrect' => false, 'order' => 2],
        ];
    }

    /**
     * Splits 100 points evenly across a quiz's questions (remainder cents
     * assigned to the first questions) so every question counts equally
     * toward the total — question-level weighting is not configurable.
     *
     * @return array<int, float>
     */
    private function equalPoints(int $count): array
    {
        if ($count === 0) {
            return [];
        }

        $baseCents = intdiv(10000, $count);
        $remainderCents = 10000 - $baseCents * $count;

        return array_map(
            fn (int $index) => ($baseCents + ($index < $remainderCents ? 1 : 0)) / 100,
            range(0, $count - 1)
        );
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
        QuizService $quizService,
        AssessmentQuestionService $assessmentQuestionService,
        AssessmentQuestionOptionService $assessmentQuestionOptionService,
        SessionService $sessionService,
    ): mixed {
        try {
            $result = $this->persist($assessmentService, $quizService, $assessmentQuestionService, $assessmentQuestionOptionService, $sessionService);
        } catch (\Throwable $exception) {
            $this->dispatch('assessmentquizform-error');

            throw $exception;
        }

        if ($result === null) {
            $this->dispatch('assessmentquizform-error');
        }

        return $result;
    }

    private function persist(
        AssessmentService $assessmentService,
        QuizService $quizService,
        AssessmentQuestionService $assessmentQuestionService,
        AssessmentQuestionOptionService $assessmentQuestionOptionService,
        SessionService $sessionService,
    ): mixed {
        $this->pruneRemoved();

        $this->validate([
            'title' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'sessionId' => 'required|string',
            'totalAttempts' => 'nullable|integer|min:1',
            'scoringMethod' => 'required|in:highest,latest,average',
            'timeLimitPerAttempt' => 'nullable|integer|min:1',
            'questions' => 'array|min:1',
            'questions.*.description' => 'required|string',
        ], [], $this->questionValidationAttributes($this->questions, ['description']));

        foreach ($this->questions as $index => $question) {
            $labelled = array_filter($question['options'], fn ($option) => trim($option['label']) !== '');
            if (count($labelled) < 2) {
                $this->addError("questions.{$index}.options", __('At least two options are required.'));
            }

            $correctCount = count(array_filter($question['options'], fn ($option) => $option['isCorrect']));
            if ($correctCount !== 1) {
                $this->addError("questions.{$index}.options", __('Exactly one option must be marked correct.'));
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        $session = $sessionService->find($this->sessionId);
        abort_if($session === null || $session->course_id !== $this->course->id, 404);

        $result = DB::transaction(function () use ($assessmentService, $quizService, $assessmentQuestionService, $assessmentQuestionOptionService, $session) {
            $assessmentData = [
                'course_id' => $this->course->id,
                'session_id' => $session->id,
                'type' => AssessmentType::TheoryQuiz,
                'title' => $this->title,
                'weight' => (float) $this->weight,
                'start_date' => $session->date_start,
                'end_date' => $session->date_end,
                'status' => AssessmentStatus::from($this->status),
            ];

            if ($this->assessment) {
                $assessmentService->update($this->assessment->id, $assessmentData);
                $assessment = $this->assessment;
            } else {
                $assessment = $assessmentService->create($assessmentData);
            }

            $quizData = [
                'assessment_id' => $assessment->id,
                'start_date' => $session->date_start,
                'due_date' => $session->date_end,
                'total_question' => count($this->questions),
                'total_attempts' => $this->totalAttempts !== '' ? (int) $this->totalAttempts : null,
                'scoring_method' => QuizScoringMethod::from($this->scoringMethod),
                'time_limit_per_attempt' => $this->timeLimitPerAttempt !== '' ? (int) $this->timeLimitPerAttempt : null,
            ];

            $quiz = $assessment->quiz;
            if ($quiz) {
                $quiz = $quizService->update($quiz->id, $quizData);
            } else {
                $quiz = $quizService->create($quizData);
            }

            $existingQuestionIds = collect($this->questions)->pluck('id')->filter()->all();
            foreach ($quiz->questions as $existingQuestion) {
                if (! in_array($existingQuestion->id, $existingQuestionIds, true)) {
                    $assessmentQuestionService->delete($existingQuestion->id);
                }
            }

            $pointsByIndex = $this->equalPoints(count($this->questions));

            foreach ($this->questions as $index => $question) {
                $questionData = [
                    'assessment_id' => $quiz->assessment_id,
                    'description' => HtmlSanitizer::forum($this->promoteRichTextAttachments($question['description'])),
                    'points' => $pointsByIndex[$index],
                    'question_type' => AssessmentQuestionType::MultipleChoice,
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

    public function render(SessionService $sessionService, QuizInstructionService $quizInstructionService)
    {
        return view('livewire.courses.assessment-quiz-form', [
            'pageTitle' => $this->assessment ? 'Edit Quiz' : 'Create Quiz',
            'sessions' => $sessionService->forCourse($this->course->id),
            'statuses' => AssessmentStatus::cases(),
            'scoringMethods' => QuizScoringMethod::cases(),
            'hasInstructions' => $quizInstructionService->current() !== null,
            'showUrl' => $this->assessment ? route('assessments.quiz.show', $this->assessment) : null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->assessment ? 'Edit Assessment' : 'Create Assessment'])
            ->section('app-content');
    }
}
