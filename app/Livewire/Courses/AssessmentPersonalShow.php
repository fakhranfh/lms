<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionScoreService;
use App\Services\AssessmentScoreService;
use App\Services\CoursePersonService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AssessmentPersonalShow extends Component
{
    use WithPagination, WithRichTextEditor;

    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public string $answerText = '';

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

    public function mount(CurrentSchool $currentSchool, CoursePersonService $coursePersonService, ?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryPersonalAssignment, 404);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);
            abort_if($assessment->status === AssessmentStatus::Draft, 404);
        }

        $this->course = $course;
        $this->assessment = $assessment;
        $this->successMessage = session('successMessage');
    }

    public function submit(AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService): bool
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $this->validate([
            'answerText' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if (trim(strip_tags($value)) === '') {
                    $fail(__('Answer cannot be empty.'));
                }
            }],
        ]);

        $previousAttempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $latest = $previousAttempts->last();

        if ($latest && $latest->score) {
            $this->errorMessage = __('This assignment has already been graded and can no longer be resubmitted.');

            return false;
        }

        if ($this->assessment->end_date && now()->greaterThan($this->assessment->end_date)) {
            $this->errorMessage = __('The submission window for this assignment has closed.');

            return false;
        }

        if ($this->assessment->start_date && now()->lessThan($this->assessment->start_date)) {
            $this->errorMessage = __('This assignment is not open yet.');

            return false;
        }

        $attempt = $assessmentAttemptService->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => auth()->id(),
            'submitted_by' => auth()->id(),
            'attempt_number' => $latest ? $latest->attempt_number + 1 : 1,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $assessmentAnswerService->create([
            'assessment_attempt_id' => $attempt->id,
            'answer_text' => HtmlSanitizer::forum($this->promoteRichTextAttachments($this->answerText)),
        ]);

        $this->answerText = '';
        $this->successMessage = __('Your submission has been recorded.');

        return true;
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    public function render(CoursePersonService $coursePersonService, AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService)
    {
        $isExpired = $this->assessment->end_date && $this->assessment->end_date->isPast();

        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'isStudent' => $this->isStudent,
            'canGrade' => auth()->user()->can('assessment.grade'),
            'canSubmit' => auth()->user()->can('assessment.submit'),
            'canEdit' => auth()->user()->can('assessment.edit'),
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
            'isExpired' => $isExpired,
            'notStarted' => $this->assessment->start_date && now()->lessThan($this->assessment->start_date),
        ];

        if ($this->isStudent) {
            $allAttempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
            $latest = $allAttempts->last();

            $attemptLimit = $this->assessment->attempt_limit;
            $attemptsUsed = $allAttempts->count();
            $canResubmit = ! $latest?->score && (! $this->assessment->end_date || now()->lessThanOrEqualTo($this->assessment->end_date));
            if ($attemptLimit && $attemptsUsed >= $attemptLimit) {
                $canResubmit = false;
            }

            $latestAnswer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
            $latestScore = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;

            $attemptRows = $allAttempts->map(function ($attempt) use ($assessmentAnswerService, $assessmentScoreService, $assessmentQuestionScoreService) {
                return [
                    'attempt' => $attempt,
                    'answer' => $assessmentAnswerService->findByAttempt($attempt->id),
                    'score' => $assessmentScoreService->findByAttempt($attempt->id),
                    'questionScores' => $assessmentQuestionScoreService->findByAttempt($attempt->id)->keyBy('assessment_question_id'),
                ];
            })->values();

            $viewData['latestAttempt'] = $latest;
            $viewData['latestAnswer'] = $latestAnswer;
            $viewData['latestScore'] = $latestScore;
            $viewData['canResubmit'] = $canResubmit;
            $viewData['attemptLimit'] = $attemptLimit ? (string) $attemptLimit : 'Unlimited';
            $viewData['attemptsUsed'] = $attemptsUsed;
            $viewData['attemptRows'] = $attemptRows;
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $search = trim($this->studentSearch);
            if ($search !== '') {
                $students = $students->filter(
                    fn ($coursePerson) => str_contains(strtolower($coursePerson->user->name), strtolower($search))
                )->values();
            }

            $rows = $students->map(function ($coursePerson) use ($assessmentAttemptService, $assessmentAnswerService, $assessmentScoreService, $assessmentQuestionScoreService) {
                $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $coursePerson->user_id);
                $latest = $attempts->last();
                $score = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
                $answer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
                $questionScores = $latest ? $assessmentQuestionScoreService->findByAttempt($latest->id) : collect();

                return [
                    'user' => $coursePerson->user,
                    'attempt' => $latest,
                    'answer' => $answer,
                    'score' => $score,
                    'questionScores' => $questionScores->keyBy('assessment_question_id'),
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

        return view('livewire.courses.assessment-personal-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
