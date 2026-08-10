<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
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

    public function openGrading(string $userId, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $userId)->last();

        if (! $attempt) {
            return;
        }

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);

        $this->gradingUserId = $userId;
        $this->gradeScore = $existingScore ? (string) $existingScore->score : '';
        $this->gradeFeedback = $existingScore ? ($existingScore->feedback ?? '') : '';
    }

    public function cancelGrading(): void
    {
        $this->gradingUserId = null;
        $this->gradeScore = '';
        $this->gradeFeedback = '';
    }

    public function submitGrade(AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);
        abort_unless($this->gradingUserId !== null, 404);

        $this->validate([
            'gradeScore' => 'required|numeric|min:0',
            'gradeFeedback' => 'nullable|string',
        ]);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->gradingUserId)->last();

        abort_unless($attempt !== null, 404);

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);

        $data = [
            'assessment_attempt_id' => $attempt->id,
            'score' => (float) $this->gradeScore,
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

    public function render(CoursePersonService $coursePersonService, AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService, AssessmentScoreService $assessmentScoreService)
    {
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
        ];

        if ($this->isStudent) {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
            $latest = $attempts->last();
            $latestAnswer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
            $latestScore = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;

            $viewData['latestAttempt'] = $latest;
            $viewData['latestAnswer'] = $latestAnswer;
            $viewData['latestScore'] = $latestScore;
            $viewData['canResubmit'] = ! $latestScore && (! $this->assessment->end_date || now()->lessThanOrEqualTo($this->assessment->end_date));
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $rows = $students->map(function ($coursePerson) use ($assessmentAttemptService, $assessmentAnswerService, $assessmentScoreService) {
                $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $coursePerson->user_id);
                $latest = $attempts->last();
                $score = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
                $answer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;

                return [
                    'user' => $coursePerson->user,
                    'attempt' => $latest,
                    'answer' => $answer,
                    'score' => $score,
                ];
            })->values();

            $viewData['studentRows'] = $rows;
        }

        return view('livewire.courses.assessment-personal-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
