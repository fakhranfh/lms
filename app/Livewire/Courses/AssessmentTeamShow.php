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
use App\Services\AssessmentScoreService;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Livewire\Component;

class AssessmentTeamShow extends Component
{
    use WithRichTextEditor;

    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public string $answerText = '';

    public ?string $gradingGroupId = null;

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
        abort_unless($assessment->type === AssessmentType::TheoryTeamAssignment, 404);

        $this->course = $course;
        $this->assessment = $assessment;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_if($assessment->status === AssessmentStatus::Draft, 404);
        }
    }

    private function ownGroupId(GroupMemberService $groupMemberService): ?string
    {
        $member = $groupMemberService->get(['user_id' => auth()->id()])
            ->first(fn ($m) => $m->group->course_id === $this->course->id);

        return $member?->group_id;
    }

    public function submit(GroupMemberService $groupMemberService, AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService): bool
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $groupId = $this->ownGroupId($groupMemberService);

        if (! $groupId) {
            $this->errorMessage = __('You are not assigned to a group for this course.');

            return false;
        }

        $this->validate([
            'answerText' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if (trim(strip_tags($value)) === '') {
                    $fail(__('Answer cannot be empty.'));
                }
            }],
        ]);

        $previousAttempts = $assessmentAttemptService->forAssessmentAndGroup($this->assessment->id, $groupId);
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
            'group_id' => $groupId,
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
        $this->successMessage = __('Your group\'s submission has been recorded.');

        return true;
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    public function openGrading(string $groupId, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $attempt = $assessmentAttemptService->forAssessmentAndGroup($this->assessment->id, $groupId)->last();

        if (! $attempt) {
            return;
        }

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);

        $this->gradingGroupId = $groupId;
        $this->gradeScore = $existingScore ? (string) $existingScore->score : '';
        $this->gradeFeedback = $existingScore ? ($existingScore->feedback ?? '') : '';
    }

    public function cancelGrading(): void
    {
        $this->gradingGroupId = null;
        $this->gradeScore = '';
        $this->gradeFeedback = '';
    }

    public function submitGrade(AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, GroupMemberService $groupMemberService, GradebookScoringService $gradebookScoringService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);
        abort_unless($this->gradingGroupId !== null, 404);

        $this->validate([
            'gradeScore' => 'required|numeric|min:0',
            'gradeFeedback' => 'nullable|string',
        ]);

        $attempt = $assessmentAttemptService->forAssessmentAndGroup($this->assessment->id, $this->gradingGroupId)->last();

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

        foreach ($groupMemberService->get(['group_id' => $this->gradingGroupId]) as $member) {
            $gradebookScoringService->recomputeForUser($this->course, $member->user_id);
        }

        $this->cancelGrading();
        $this->successMessage = __('Grade saved.');
    }

    public function render(CoursePersonService $coursePersonService, GroupService $groupService, GroupMemberService $groupMemberService, AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService, AssessmentScoreService $assessmentScoreService)
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
            'notStarted' => $this->assessment->start_date && now()->lessThan($this->assessment->start_date),
        ];

        if ($this->isStudent) {
            $groupId = $this->ownGroupId($groupMemberService);
            $group = $groupId ? $groupService->find($groupId, ['members.user']) : null;

            $allAttempts = $groupId ? $assessmentAttemptService->forAssessmentAndGroup($this->assessment->id, $groupId) : collect();
            $latest = $allAttempts->last();

            $attemptLimit = $this->assessment->attempt_limit;
            $attemptsUsed = $allAttempts->count();
            $canResubmit = ! $latest?->score && (! $this->assessment->end_date || now()->lessThanOrEqualTo($this->assessment->end_date));
            if ($attemptLimit && $attemptsUsed >= $attemptLimit) {
                $canResubmit = false;
            }

            $latestAnswer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
            $latestScore = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;

            $attemptRows = $allAttempts->map(function ($attempt) use ($assessmentAnswerService, $assessmentScoreService) {
                return [
                    'attempt' => $attempt,
                    'answer' => $assessmentAnswerService->findByAttempt($attempt->id),
                    'score' => $assessmentScoreService->findByAttempt($attempt->id),
                ];
            })->values();

            $viewData['group'] = $group;
            $viewData['latestAttempt'] = $latest;
            $viewData['latestAnswer'] = $latestAnswer;
            $viewData['latestScore'] = $latestScore;
            $viewData['canResubmit'] = $canResubmit;
            $viewData['attemptLimit'] = $attemptLimit ? (string) $attemptLimit : 'Unlimited';
            $viewData['attemptsUsed'] = $attemptsUsed;
            $viewData['attemptRows'] = $attemptRows;
        } else {
            $groups = $groupService->forCourse($this->course->id);

            $rows = $groups->map(function ($group) use ($assessmentAttemptService, $assessmentAnswerService, $assessmentScoreService) {
                $attempts = $assessmentAttemptService->forAssessmentAndGroup($this->assessment->id, $group->id);
                $latest = $attempts->last();
                $score = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
                $answer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;

                return [
                    'group' => $group,
                    'attempt' => $latest,
                    'answer' => $answer,
                    'score' => $score,
                ];
            })->values();

            $viewData['groupRows'] = $rows;
        }

        return view('livewire.courses.assessment-team-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
