<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\QuizQuestionType;
use App\Enums\QuizScoringMethod;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentService;
use App\Services\QuizInstructionService;
use App\Services\QuizQuestionOptionService;
use App\Services\QuizQuestionService;
use App\Services\QuizService;
use App\Services\SessionService;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssessmentQuizForm extends Component
{
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
     * @var array<int, array{id: ?string, description: string, points: string, questionType: string, order: int, options: array<int, array{id: ?string, label: string, isCorrect: bool, order: int}>}>
     */
    public array $questions = [];

    public function mount(CurrentSchool $currentSchool, ?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $course ??= $assessment?->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
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
                    'points' => (string) $question->points,
                    'questionType' => $question->question_type->value,
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
            'questionType' => QuizQuestionType::MultipleChoice->value,
            'order' => count($this->questions) + 1,
            'options' => $this->defaultOptions(QuizQuestionType::MultipleChoice->value),
        ];
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function setQuestionType(int $index, string $type): void
    {
        $this->questions[$index]['questionType'] = $type;
        $this->questions[$index]['options'] = $this->defaultOptions($type);
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
    private function defaultOptions(string $type): array
    {
        return match ($type) {
            QuizQuestionType::TrueFalse->value => [
                ['id' => null, 'label' => 'True', 'isCorrect' => false, 'order' => 1],
                ['id' => null, 'label' => 'False', 'isCorrect' => false, 'order' => 2],
            ],
            QuizQuestionType::MultipleChoice->value => [
                ['id' => null, 'label' => '', 'isCorrect' => false, 'order' => 1],
                ['id' => null, 'label' => '', 'isCorrect' => false, 'order' => 2],
            ],
            default => [],
        };
    }

    public function save(
        AssessmentService $assessmentService,
        QuizService $quizService,
        QuizQuestionService $quizQuestionService,
        QuizQuestionOptionService $quizQuestionOptionService,
        SessionService $sessionService,
    ): mixed {
        $this->validate([
            'title' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'sessionId' => 'required|string',
            'totalAttempts' => 'nullable|integer|min:1',
            'scoringMethod' => 'required|in:highest,latest,average',
            'timeLimitPerAttempt' => 'nullable|integer|min:1',
            'questions' => 'array|min:1',
            'questions.*.description' => 'required|string',
            'questions.*.points' => 'required|numeric|min:0',
            'questions.*.questionType' => 'required|in:multiple_choice,true_false,short_answer,essay',
        ]);

        foreach ($this->questions as $index => $question) {
            if (in_array($question['questionType'], [QuizQuestionType::MultipleChoice->value, QuizQuestionType::TrueFalse->value], true)) {
                $labelled = array_filter($question['options'], fn ($option) => trim($option['label']) !== '');
                if (count($labelled) < 2) {
                    $this->addError("questions.{$index}.options", __('At least two options are required.'));
                }

                $correctCount = count(array_filter($question['options'], fn ($option) => $option['isCorrect']));
                if ($correctCount !== 1) {
                    $this->addError("questions.{$index}.options", __('Exactly one option must be marked correct.'));
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        $session = $sessionService->find($this->sessionId);
        abort_if($session === null || $session->course_id !== $this->course->id, 404);

        $result = DB::transaction(function () use ($assessmentService, $quizService, $quizQuestionService, $quizQuestionOptionService, $session) {
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
                    $quizQuestionService->delete($existingQuestion->id);
                }
            }

            foreach ($this->questions as $index => $question) {
                $questionData = [
                    'quiz_id' => $quiz->id,
                    'description' => HtmlSanitizer::forum($question['description']),
                    'points' => (float) $question['points'],
                    'question_type' => QuizQuestionType::from($question['questionType']),
                    'order' => $index + 1,
                ];

                if ($question['id']) {
                    $questionModel = $quizQuestionService->update($question['id'], $questionData);
                } else {
                    $questionModel = $quizQuestionService->create($questionData);
                }

                $existingOptionIds = collect($question['options'])->pluck('id')->filter()->all();
                foreach ($questionModel->options as $existingOption) {
                    if (! in_array($existingOption->id, $existingOptionIds, true)) {
                        $quizQuestionOptionService->delete($existingOption->id);
                    }
                }

                foreach ($question['options'] as $optionIndex => $option) {
                    if (trim($option['label']) === '') {
                        continue;
                    }

                    $optionData = [
                        'quiz_question_id' => $questionModel->id,
                        'label' => $option['label'],
                        'is_correct' => $option['isCorrect'],
                        'order' => $optionIndex + 1,
                    ];

                    if ($option['id']) {
                        $quizQuestionOptionService->update($option['id'], $optionData);
                    } else {
                        $quizQuestionOptionService->create($optionData);
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
            'questionTypes' => QuizQuestionType::cases(),
            'hasInstructions' => $quizInstructionService->current() !== null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->assessment ? 'Edit Assessment' : 'Create Assessment'])
            ->section('app-content');
    }
}
