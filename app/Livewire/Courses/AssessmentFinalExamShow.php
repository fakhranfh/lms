<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSnapshotType;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionScoreService;
use App\Services\AssessmentScoreService;
use App\Services\CoursePersonService;
use App\Services\FinalExamService;
use App\Services\ProctorSessionService;
use App\Services\R2StorageService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Livewire\Component;

class AssessmentFinalExamShow extends Component
{
    use WithRichTextEditor;

    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public string $answerText = '';

    public ?string $gradingUserId = null;

    public string $gradeScore = '';

    public string $gradeFeedback = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public array $gradeQuestionScores = [];

    /**
     * @var array<string, string>
     */
    public array $reviewDecision = [];

    /**
     * @var array<string, string>
     */
    public array $reviewNotes = [];

    public function mount(CurrentSchool $currentSchool, CoursePersonService $coursePersonService, ?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);
        }

        $this->course = $course;
        $this->assessment = $assessment;
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
            $this->errorMessage = __('This final exam has already been graded and can no longer be resubmitted.');

            return false;
        }

        if ($this->assessment->end_date && now()->greaterThan($this->assessment->end_date)) {
            $this->errorMessage = __('The submission window for this final exam has closed.');

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

    public function reviewProctorSession(
        string $proctorSessionId,
        ProctorSessionService $proctorSessionService,
        AssessmentScoreService $assessmentScoreService,
    ): void {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $decisionEnum = ProctorReviewDecision::from($this->reviewDecision[$proctorSessionId] ?? 'no_action');
        $notes = $this->reviewNotes[$proctorSessionId] ?? null;

        $session = $proctorSessionService->update($proctorSessionId, [
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_decision' => $decisionEnum,
            'review_notes' => $notes,
        ]);

        if ($decisionEnum === ProctorReviewDecision::Disqualified) {
            $score = $assessmentScoreService->findByAttempt($session->assessment_attempt_id);

            if ($score) {
                $assessmentScoreService->update($score->id, ['score' => 0]);
            } else {
                $assessmentScoreService->create([
                    'assessment_attempt_id' => $session->assessment_attempt_id,
                    'score' => 0,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                    'feedback' => __('Disqualified due to proctoring violation.'),
                ]);
            }
        }

        $this->successMessage = __('Proctoring review saved.');
    }

    public function recordingUrl(string $key): string
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);
        abort_unless(str_contains($key, '/proctor/'), 403);

        return app(R2StorageService::class)->getSignedUrl($key, 3600);
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

    public function render(CoursePersonService $coursePersonService, AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService, FinalExamService $finalExamService, ProctorSessionService $proctorSessionService)
    {
        $isExpired = $this->assessment->end_date && $this->assessment->end_date->isPast();
        $finalExam = $finalExamService->findByAssessment($this->assessment->id);
        $isProctored = $finalExam && in_array($finalExam->exam_type->value, ['open_book', 'closed_book']);

        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'finalExam' => $finalExam,
            'isProctored' => $isProctored,
            'isStudent' => $this->isStudent,
            'canGrade' => auth()->user()->can('assessment.grade'),
            'canSubmit' => auth()->user()->can('assessment.submit'),
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
            'isExpired' => $isExpired,
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

            $latestScore = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
            $latestProctorSession = ($isProctored && $latest) ? $proctorSessionService->findByAttempt($latest->id) : null;

            $viewData['latestAttempt'] = $latest;
            $viewData['latestScore'] = $latestScore;
            $viewData['pendingProctorReview'] = $latestProctorSession !== null && $latestProctorSession->reviewed_at === null;
            $viewData['canResubmit'] = $canResubmit;
            $viewData['attemptLimit'] = $attemptLimit ? (string) $attemptLimit : 'Unlimited';
            $viewData['attemptsUsed'] = $attemptsUsed;
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $rows = $students->map(function ($coursePerson) use ($assessmentAttemptService, $assessmentAnswerService, $assessmentScoreService, $assessmentQuestionScoreService, $proctorSessionService, $isProctored) {
                $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $coursePerson->user_id);
                $latest = $attempts->last();
                $score = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
                $answer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
                $questionScores = $latest ? $assessmentQuestionScoreService->findByAttempt($latest->id) : collect();
                $proctorSession = ($isProctored && $latest)
                    ? $proctorSessionService->findByAttempt($latest->id, ['events', 'snapshots'])
                    : null;

                $recordings = $proctorSession
                    ? $proctorSession->snapshots->where('type', ProctorSnapshotType::Recording)->sortBy('captured_at')
                    : collect();

                $pendingProctorReview = $proctorSession !== null && $proctorSession->reviewed_at === null;

                $screenshots = $proctorSession
                    ? $proctorSession->snapshots->where('type', ProctorSnapshotType::Screen)->sortBy('captured_at')->values()
                    : collect();

                $screenshotsByEvent = $screenshots->groupBy(fn ($shot) => $shot->triggered_by_event_id ?? 'none');

                return [
                    'user' => $coursePerson->user,
                    'attempt' => $latest,
                    'answer' => $answer,
                    'score' => $score,
                    'questionScores' => $questionScores->keyBy('assessment_question_id'),
                    'proctorSession' => $proctorSession,
                    'pendingProctorReview' => $pendingProctorReview,
                    'cameraRecordings' => $recordings->filter(fn ($s) => str_contains($s->file_url, 'webcam-recording'))->values(),
                    'screenRecordings' => $recordings->filter(fn ($s) => str_contains($s->file_url, 'screen-recording'))->values(),
                    'screenshots' => $screenshots,
                    'screenshotsByEvent' => $screenshotsByEvent,
                ];
            })->values();

            $viewData['studentRows'] = $rows;
        }

        return view('livewire.courses.assessment-final-exam-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
