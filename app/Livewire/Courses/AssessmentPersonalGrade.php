<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionScoreService;
use App\Services\AssessmentScoreService;
use App\Services\GradebookScoringService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class AssessmentPersonalGrade extends Component
{
    public Course $course;

    public Assessment $assessment;

    public User $student;

    public string $gradeFeedback = '';

    public array $gradeQuestionScores = [];

    public ?string $errorMessage = null;

    public function mount(CurrentSchool $currentSchool, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService, Assessment $assessment, User $student): void
    {
        $course = $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.grade') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->type === AssessmentType::TheoryPersonalAssignment, 404);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($assessment->id, $student->id)->last();
        abort_if($attempt === null, 404);

        $this->course = $course;
        $this->assessment = $assessment;
        $this->student = $student;

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $this->gradeFeedback = $existingScore ? ($existingScore->feedback ?? '') : '';

        $questionScores = $assessmentQuestionScoreService->findByAttempt($attempt->id);
        foreach ($this->assessment->questions as $question) {
            $qScore = $questionScores->firstWhere('assessment_question_id', $question->id);
            $this->gradeQuestionScores[$question->id] = $qScore ? (string) $qScore->score : '';
        }
    }

    public function submitGrade(AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService, GradebookScoringService $gradebookScoringService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $this->validate([
            'gradeFeedback' => 'nullable|string',
        ]);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->student->id)->last();
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

        $gradebookScoringService->recomputeForUser($this->course, $this->student->id);

        session()->flash('successMessage', __('Grade saved.'));

        $this->redirectRoute('assessments.personal.show', ['assessment' => $this->assessment->id], navigate: false);
    }

    public function render(AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService)
    {
        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->student->id)->last();
        $answer = $attempt ? $assessmentAnswerService->findByAttempt($attempt->id) : null;

        return view('livewire.courses.assessment-personal-grade', [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'student' => $this->student,
            'attempt' => $attempt,
            'answer' => $answer,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
