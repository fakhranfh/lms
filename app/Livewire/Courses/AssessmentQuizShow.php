<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionAnswerService;
use App\Services\AssessmentQuestionAttemptScoringService;
use App\Services\AssessmentScoreService;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
use App\Services\QuizInstructionService;
use App\Services\QuizService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AssessmentQuizShow extends Component
{
    use WithPagination;

    public Course $course;

    public Assessment $assessment;

    public Quiz $quiz;

    public bool $isStudent = false;

    /**
     * @var array<string, string>
     */
    public array $answers = [];

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public int $perPage = 12;

    public string $studentSearch = '';

    public string $submissionFilter = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['perPage', 'studentSearch', 'submissionFilter'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @param  array{attempt: mixed, score: mixed}  $row
     */
    private function submissionStatus(array $row): string
    {
        if (! $row['attempt']) {
            return 'not_submitted';
        }

        return $row['score'] ? 'graded' : 'submitted';
    }

    public function mount(
        CurrentSchool $currentSchool,
        CoursePersonService $coursePersonService,
        QuizService $quizService,
        ?Course $course = null,
        ?Assessment $assessment = null,
    ): void {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryQuiz, 404);

        $quiz = $quizService->findByAssessment($assessment->id, ['questions.options']);
        abort_if($quiz === null, 404);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);
            abort_if($assessment->status === AssessmentStatus::Draft, 404);
        }

        $this->course = $course;
        $this->assessment = $assessment;
        $this->quiz = $quiz;
    }

    public function startAttempt(AssessmentAttemptService $assessmentAttemptService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $inProgress = $attempts->first(fn ($attempt) => $attempt->submitted_at === null);

        if ($inProgress) {
            $this->answers = [];

            return;
        }

        if ($this->quiz->total_attempts !== null && $attempts->count() >= $this->quiz->total_attempts) {
            $this->errorMessage = __('You have reached the maximum number of attempts for this quiz.');

            return;
        }

        if ($this->assessment->end_date && now()->greaterThan($this->assessment->end_date)) {
            $this->errorMessage = __('The submission window for this quiz has closed.');

            return;
        }

        $assessmentAttemptService->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => auth()->id(),
            'submitted_by' => auth()->id(),
            'attempt_number' => $attempts->count() + 1,
            'started_at' => now(),
        ]);

        $this->answers = [];
    }

    public function submitAttempt(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentQuestionAnswerService $assessmentQuestionAnswerService,
        AssessmentQuestionAttemptScoringService $assessmentQuestionAttemptScoringService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $attempt = $attempts->first(fn ($a) => $a->submitted_at === null);

        if (! $attempt) {
            $this->errorMessage = __('No attempt in progress.');

            return;
        }

        foreach ($this->quiz->questions as $question) {
            $value = $this->answers[$question->id] ?? null;

            $isObjective = in_array($question->question_type, [AssessmentQuestionType::MultipleChoice, AssessmentQuestionType::TrueFalse], true);

            $assessmentQuestionAnswerService->create([
                'assessment_attempt_id' => $attempt->id,
                'assessment_question_id' => $question->id,
                'selected_option_id' => $isObjective ? ($value ?: null) : null,
                'answer_text' => $isObjective ? null : ($value ?: null),
                'score' => $isObjective ? $assessmentQuestionAttemptScoringService->scoreObjectiveAnswer($question, $value ?: null) : null,
            ]);
        }

        $deadline = $this->quiz->time_limit_per_attempt
            ? $attempt->started_at->copy()->addMinutes($this->quiz->time_limit_per_attempt)
            : null;

        $assessmentAttemptService->update($attempt->id, [
            'submitted_at' => $deadline && now()->greaterThan($deadline) ? $deadline : now(),
        ]);

        $assessmentQuestionAttemptScoringService->recomputeForUser($this->quiz, $this->assessment->id, auth()->id());
        $gradebookScoringService->recomputeForUser($this->course, auth()->id());

        $this->answers = [];
        $this->successMessage = __('Your quiz has been submitted.');
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    public function render(
        CoursePersonService $coursePersonService,
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentQuestionAnswerService $assessmentQuestionAnswerService,
        AssessmentScoreService $assessmentScoreService,
        QuizInstructionService $quizInstructionService,
        AssessmentQuestionAttemptScoringService $assessmentQuestionAttemptScoringService,
    ) {
        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'quiz' => $this->quiz,
            'isStudent' => $this->isStudent,
            'canSubmit' => auth()->user()->can('assessment.submit'),
            'canEdit' => auth()->user()->can('assessment.edit'),
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
            'instruction' => $quizInstructionService->current(),
            'isExpired' => $this->assessment->end_date && $this->assessment->end_date->isPast(),
        ];

        if ($this->isStudent) {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
            $inProgress = $attempts->first(fn ($a) => $a->submitted_at === null);
            $submittedAttempts = $attempts->filter(fn ($a) => $a->submitted_at !== null)->values();

            $attemptRows = $submittedAttempts->map(function ($attempt) use ($assessmentQuestionAnswerService, $assessmentScoreService, $assessmentQuestionAttemptScoringService) {
                return [
                    'attempt' => $attempt,
                    'total' => $assessmentQuestionAttemptScoringService->attemptTotal($attempt->id),
                    'pending' => $assessmentQuestionAttemptScoringService->hasPendingGrading($attempt->id),
                    'score' => $assessmentScoreService->findByAttempt($attempt->id),
                    'answers' => $assessmentQuestionAnswerService->forAttempt($attempt->id)->keyBy('assessment_question_id'),
                ];
            })->reverse()->values();

            $attemptsUsed = $submittedAttempts->count() + ($inProgress ? 1 : 0);
            $canStart = ! $inProgress
                && (! $this->quiz->total_attempts || $attemptsUsed < $this->quiz->total_attempts)
                && ! $viewData['isExpired'];

            $viewData['inProgress'] = $inProgress;
            $viewData['attemptRows'] = $attemptRows;
            $viewData['currentScore'] = $attemptRows->pluck('score')->filter()->first();
            $viewData['attemptsUsed'] = $attemptsUsed;
            $viewData['attemptLimit'] = $this->quiz->total_attempts ? (string) $this->quiz->total_attempts : 'Unlimited';
            $viewData['canStart'] = $canStart;
            $viewData['deadlineIso'] = ($inProgress && $this->quiz->time_limit_per_attempt)
                ? $inProgress->started_at->copy()->addMinutes($this->quiz->time_limit_per_attempt)->toIso8601String()
                : null;
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $search = trim($this->studentSearch);
            if ($search !== '') {
                $students = $students->filter(
                    fn ($coursePerson) => str_contains(strtolower($coursePerson->user->name), strtolower($search))
                )->values();
            }

            $rows = $students->map(function ($coursePerson) use ($assessmentAttemptService, $assessmentQuestionAnswerService, $assessmentScoreService, $assessmentQuestionAttemptScoringService) {
                $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $coursePerson->user_id)
                    ->filter(fn ($a) => $a->submitted_at !== null)
                    ->values();
                $latest = $attempts->last();
                $score = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
                $pending = $latest ? $assessmentQuestionAttemptScoringService->hasPendingGrading($latest->id) : false;

                return [
                    'user' => $coursePerson->user,
                    'attemptCount' => $attempts->count(),
                    'attempt' => $latest,
                    'total' => $latest ? $assessmentQuestionAttemptScoringService->attemptTotal($latest->id) : null,
                    'score' => $score,
                    'pending' => $pending,
                    'answers' => $latest ? $assessmentQuestionAnswerService->forAttempt($latest->id)->keyBy('assessment_question_id') : collect(),
                ];
            })->values();

            if ($this->submissionFilter !== '') {
                $rows = $rows->filter(fn (array $row) => $this->submissionStatus($row) === $this->submissionFilter)->values();
            }

            $page = $this->getPage();

            $viewData['studentRows'] = new LengthAwarePaginator(
                $rows->forPage($page, $this->perPage)->values(),
                $rows->count(),
                $this->perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        return view('livewire.courses.assessment-quiz-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
