<?php

namespace App\Livewire\Courses\Concerns;

use App\Services\AssessmentAttemptService;
use App\Services\AssessmentService;
use App\Services\AttendanceService;
use App\Services\CoursePersonService;
use App\Services\ForumService;
use App\Services\GradebookRandomizerService;
use App\Services\GradebookScoringService;
use Livewire\Attributes\On;

trait HasGradebookIndexDevTools
{
    public bool $isLocalEnv = false;

    public function randomizeScores(GradebookRandomizerService $gradebookRandomizerService): void
    {
        abort_unless(app()->environment('local'), 403);
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $this->successMessage = null;

        $count = $gradebookRandomizerService->randomizeForCourse($this->course, auth()->id());

        if ($count > 0) {
            $this->successMessage = __('Randomized gradebook scores for :count student(s).', ['count' => $count]);
        }
    }

    #[On('delete-confirmed')]
    public function resetScores(
        AssessmentService $assessmentService,
        AssessmentAttemptService $assessmentAttemptService,
        AttendanceService $attendanceService,
        ForumService $forumService,
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        abort_unless(app()->environment('local'), 403);
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $this->successMessage = null;

        foreach ($assessmentService->forCourse($this->course->id) as $assessment) {
            foreach ($assessmentAttemptService->get(['assessment_id' => $assessment->id]) as $attempt) {
                $assessmentAttemptService->delete($attempt->id);
            }
        }

        $attendanceService->deleteForCourse($this->course->id);

        foreach ($forumService->get(['course_id' => $this->course->id]) as $forum) {
            $forumService->delete($forum->id);
        }

        $students = $coursePersonService->studentsForCourse($this->course->id);

        foreach ($students as $coursePerson) {
            $gradebookScoringService->recomputeForUser($this->course, $coursePerson->user_id);
        }

        $this->successMessage = __('Reset all gradebook scores for this course.');
    }
}
