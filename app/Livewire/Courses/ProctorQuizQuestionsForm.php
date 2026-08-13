<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\QuizQuestionType;
use App\Enums\QuizScoringMethod;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\FinalExamService;
use App\Services\QuizQuestionOptionService;
use App\Services\QuizQuestionService;
use App\Services\QuizService;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProctorQuizQuestionsForm extends Component
{
    public Course $course;

    public Assessment $assessment;

    public string $totalAttempts = '';

    public string $scoringMethod = 'highest';

    public string $timeLimitPerAttempt = '';

    /**
     * @var array<int, array{id: ?string, description: string, points: string, order: int, options: array<int, array{id: ?string, label: string, isCorrect: bool, order: int}>}>
     */
    public array $questions = [];

    public function mount(
        CurrentSchool $currentSchool,
        FinalExamService $finalExamService,
        Course $course,
        Assessment $assessment,
    ): void {
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);

        $finalExam = $finalExamService->findByAssessment($assessment->id);
        abort_if($finalExam === null, 404);
        abort_unless(in_array($finalExam->exam_type, [FinalExamType::OpenBook, FinalExamType::ClosedBook], true), 404);

        $this->course = $course;
        $this->assessment = $assessment;

        $quiz = $assessment->quiz;
        if ($quiz) {
            $this->totalAttempts = $quiz->total_attempts !== null ? (string) $quiz->total_attempts : '';
            $this->scoringMethod = $quiz->scoring_method->value;
            $this->timeLimitPerAttempt = $quiz->time_limit_per_attempt !== null ? (string) $quiz->time_limit_per_attempt : '';

            $this->questions = $quiz->questions->map(fn ($question) => [
                'id' => $question->id,
                'description' => $question->description,
                'points' => (string) $question->points,
                'order' => $question->order,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'isCorrect' => $option->is_correct,
                    'order' => $option->order,
                ])->all(),
            ])->all();
        } else {
            $this->totalAttempts = '1';
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

    public function save(
        QuizService $quizService,
        QuizQuestionService $quizQuestionService,
        QuizQuestionOptionService $quizQuestionOptionService,
    ): mixed {
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $this->validate([
            'totalAttempts' => 'nullable|integer|min:1',
            'scoringMethod' => 'required|in:highest,latest,average',
            'timeLimitPerAttempt' => 'nullable|integer|min:1',
            'questions' => 'array|min:1',
            'questions.*.description' => 'required|string',
            'questions.*.points' => 'required|numeric|min:0',
        ]);

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

        DB::transaction(function () use ($quizService, $quizQuestionService, $quizQuestionOptionService) {
            $quizData = [
                'assessment_id' => $this->assessment->id,
                'start_date' => $this->assessment->start_date,
                'due_date' => $this->assessment->end_date,
                'total_question' => count($this->questions),
                'total_attempts' => $this->totalAttempts !== '' ? (int) $this->totalAttempts : null,
                'scoring_method' => QuizScoringMethod::from($this->scoringMethod),
                'time_limit_per_attempt' => $this->timeLimitPerAttempt !== '' ? (int) $this->timeLimitPerAttempt : null,
            ];

            $quiz = $this->assessment->quiz;
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
                    'question_type' => QuizQuestionType::MultipleChoice,
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
        });

        return redirect()->route('assessments.final-exam.show', $this->assessment);
    }

    public function render()
    {
        return view('livewire.courses.proctor-quiz-questions-form', [
            'pageTitle' => 'Manage Exam Questions',
            'scoringMethods' => QuizScoringMethod::cases(),
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Manage Exam Questions'])
            ->section('app-content');
    }
}
