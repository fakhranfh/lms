<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Group;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentScoreService;
use App\Services\GradebookScoringService;
use App\Services\GroupMemberService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class AssessmentTeamGrade extends Component
{
    public Course $course;

    public Assessment $assessment;

    public Group $group;

    public string $gradeScore = '';

    public string $gradeFeedback = '';

    public ?string $errorMessage = null;

    public function mount(CurrentSchool $currentSchool, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, Assessment $assessment, Group $group): void
    {
        $course = $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.grade') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->type === AssessmentType::TheoryTeamAssignment, 404);
        abort_unless($group->course_id === $course->id, 404);

        $attempt = $assessmentAttemptService->forAssessmentAndGroup($assessment->id, $group->id)->last();
        abort_if($attempt === null, 404);

        $this->course = $course;
        $this->assessment = $assessment;
        $this->group = $group;

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $this->gradeScore = $existingScore ? (string) $existingScore->score : '';
        $this->gradeFeedback = $existingScore ? ($existingScore->feedback ?? '') : '';
    }

    public function submitGrade(AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, GroupMemberService $groupMemberService, GradebookScoringService $gradebookScoringService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $this->validate([
            'gradeScore' => 'required|numeric|min:0',
            'gradeFeedback' => 'nullable|string',
        ]);

        $attempt = $assessmentAttemptService->forAssessmentAndGroup($this->assessment->id, $this->group->id)->last();
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

        foreach ($groupMemberService->get(['group_id' => $this->group->id]) as $member) {
            $gradebookScoringService->recomputeForUser($this->course, $member->user_id);
        }

        session()->flash('successMessage', __('Grade saved.'));

        $this->redirectRoute('assessments.team.show', ['assessment' => $this->assessment->id], navigate: false);
    }

    public function render(AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService)
    {
        $attempt = $assessmentAttemptService->forAssessmentAndGroup($this->assessment->id, $this->group->id)->last();
        $answer = $attempt ? $assessmentAnswerService->findByAttempt($attempt->id) : null;

        return view('livewire.courses.assessment-team-grade', [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'group' => $this->group,
            'attempt' => $attempt,
            'answer' => $answer,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
