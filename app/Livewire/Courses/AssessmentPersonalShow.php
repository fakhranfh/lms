<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
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
use Livewire\Component;

class AssessmentPersonalShow extends Component
{
    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public string $answerText = '';

    public ?string $gradingUserId = null;

    public string $gradeScore = '';

    public string $gradeFeedback = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public bool $isModalOpen = false;

    public ?string $viewingAttemptId = null;

    public array $gradeQuestionScores = [];

    public function mount(CurrentSchool $currentSchool, ?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryPersonalAssignment, 404);

        $this->course = $course;
        $this->assessment = $assessment;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function submit(AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $this->validate([
            'answerText' => 'required|string',
        ]);

        $previousAttempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $latest = $previousAttempts->last();

        if ($latest && $latest->score) {
            $this->errorMessage = __('This assignment has already been graded and can no longer be resubmitted.');

            return;
        }

        if ($this->assessment->end_date && now()->greaterThan($this->assessment->end_date)) {
            $this->errorMessage = __('The submission window for this assignment has closed.');

            return;
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
            'answer_text' => HtmlSanitizer::forum($this->answerText),
        ]);

        $this->answerText = '';
        $this->successMessage = __('Your submission has been recorded.');
    }

    public function openGrading(string $userId, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $userId)->last();

        if (! $attempt) {
            return;
        }

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $questionScores = $assessmentQuestionScoreService->findByAttempt($attempt->id);

        $this->gradingUserId = $userId;
        $this->gradeFeedback = $existingScore ? ($existingScore->feedback ?? '') : '';

        $this->gradeQuestionScores = [];
        foreach ($this->assessment->questions as $question) {
            $qScore = $questionScores->firstWhere('assessment_question_id', $question->id);
            $this->gradeQuestionScores[$question->id] = $qScore ? (string) $qScore->score : '';
        }
    }

    public function cancelGrading(): void
    {
        $this->gradingUserId = null;
        $this->gradeScore = '';
        $this->gradeFeedback = '';
    }

    public function openAttemptForViewing(string $attemptId): void
    {
        abort_unless(auth()->user()->can('assessment.view'), 403);
        $this->isModalOpen = true;
        $this->viewingAttemptId = $attemptId;
    }

    public function openAttemptForSubmission(): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);
        $this->isModalOpen = true;
        $this->viewingAttemptId = null;
        $this->answerText = '';
    }

    public function closeAttemptDetail(): void
    {
        $this->isModalOpen = false;
        $this->viewingAttemptId = null;
        $this->answerText = '';
    }

    public function submitGrade(AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);
        abort_unless($this->gradingUserId !== null, 404);

        $this->validate([
            'gradeFeedback' => 'nullable|string',
        ]);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->gradingUserId)->last();
        abort_unless($attempt !== null, 404);

        $totalScore = 0;
        foreach ($this->assessment->questions as $question) {
            $score = $this->gradeQuestionScores[$question->id] ?? '';
            if ($score === '') {
                $this->addError("gradeQuestionScores.{$question->id}", __('Score is required'));

                continue;
            }

            if (! is_numeric($score) || (float) $score < 0 || (float) $score > $question->points) {
                $this->addError("gradeQuestionScores.{$question->id}", __('Score must be between 0 and '.$question->points));

                continue;
            }

            $totalScore += (float) $score;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        foreach ($this->assessment->questions as $question) {
            $score = (float) $this->gradeQuestionScores[$question->id];
            $assessmentQuestionScoreService->updateOrCreate(
                ['assessment_attempt_id' => $attempt->id, 'assessment_question_id' => $question->id],
                ['score' => $score]
            );
        }

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $data = [
            'assessment_attempt_id' => $attempt->id,
            'score' => $totalScore,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
            'feedback' => $this->gradeFeedback ?: null,
        ];

        if ($existingScore) {
            $assessmentScoreService->update($existingScore->id, $data);
        } else {
            $assessmentScoreService->create($data);
        }

        $this->cancelGrading();
        $this->successMessage = __('Grade saved.');
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
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
            'isExpired' => $isExpired,
            'viewingAttemptId' => $this->viewingAttemptId,
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
            $viewData['viewingAttempt'] = $this->viewingAttemptId ? $attemptRows->firstWhere('attempt.id', $this->viewingAttemptId) : null;
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

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

            $viewData['studentRows'] = $rows;
        }

        return view('livewire.courses.assessment-personal-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
