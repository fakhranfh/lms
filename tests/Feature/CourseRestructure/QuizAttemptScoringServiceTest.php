<?php

use App\Enums\QuizScoringMethod;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuizAnswer;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\User;
use App\Services\QuizAttemptScoringService;

test('objective answer is scored against the correct option', function () {
    $question = QuizQuestion::factory()->create(['question_type' => 'multiple_choice', 'points' => 10]);
    $correct = QuizQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true]);
    $wrong = QuizQuestionOption::factory()->for($question, 'question')->create(['is_correct' => false]);
    $question->load('options');

    $service = app(QuizAttemptScoringService::class);

    expect($service->scoreObjectiveAnswer($question, $correct->id))->toBe(10.0);
    expect($service->scoreObjectiveAnswer($question, $wrong->id))->toBe(0.0);
    expect($service->scoreObjectiveAnswer($question, null))->toBe(0.0);
});

test('essay questions are not auto-scored', function () {
    $question = QuizQuestion::factory()->create(['question_type' => 'essay', 'points' => 10]);

    $service = app(QuizAttemptScoringService::class);

    expect($service->scoreObjectiveAnswer($question, null))->toBeNull();
});

test('scoring method highest picks the best submitted attempt', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create(['scoring_method' => QuizScoringMethod::Highest]);
    $question = QuizQuestion::factory()->for($quiz)->create(['points' => 10]);
    $user = User::factory()->create();

    $attempt1 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 1, 'submitted_at' => now()->subHour()]);
    AssessmentQuizAnswer::factory()->for($attempt1, 'attempt')->for($question, 'question')->create(['score' => 3]);

    $attempt2 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 2, 'submitted_at' => now()]);
    AssessmentQuizAnswer::factory()->for($attempt2, 'attempt')->for($question, 'question')->create(['score' => 10]);

    $service = app(QuizAttemptScoringService::class);
    $counted = $service->recomputeForUser($quiz, $assessment->id, $user->id);

    expect($counted->id)->toBe($attempt2->id);
    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt2->id, 'score' => 10]);
});

test('scoring method latest picks the most recently submitted attempt', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create(['scoring_method' => QuizScoringMethod::Latest]);
    $question = QuizQuestion::factory()->for($quiz)->create(['points' => 10]);
    $user = User::factory()->create();

    $attempt1 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 1, 'submitted_at' => now()->subHour()]);
    AssessmentQuizAnswer::factory()->for($attempt1, 'attempt')->for($question, 'question')->create(['score' => 10]);

    $attempt2 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 2, 'submitted_at' => now()]);
    AssessmentQuizAnswer::factory()->for($attempt2, 'attempt')->for($question, 'question')->create(['score' => 4]);

    $service = app(QuizAttemptScoringService::class);
    $counted = $service->recomputeForUser($quiz, $assessment->id, $user->id);

    expect($counted->id)->toBe($attempt2->id);
    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt2->id, 'score' => 4]);
});

test('scoring method average computes the mean total across attempts', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create(['scoring_method' => QuizScoringMethod::Average]);
    $question = QuizQuestion::factory()->for($quiz)->create(['points' => 10]);
    $user = User::factory()->create();

    $attempt1 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 1, 'submitted_at' => now()->subHour()]);
    AssessmentQuizAnswer::factory()->for($attempt1, 'attempt')->for($question, 'question')->create(['score' => 4]);

    $attempt2 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 2, 'submitted_at' => now()]);
    AssessmentQuizAnswer::factory()->for($attempt2, 'attempt')->for($question, 'question')->create(['score' => 8]);

    $service = app(QuizAttemptScoringService::class);
    $service->recomputeForUser($quiz, $assessment->id, $user->id);

    $this->assertDatabaseHas('assessment_scores', ['score' => 6]);
});

test('partial total treats an ungraded essay answer as zero', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create();
    $mcQuestion = QuizQuestion::factory()->for($quiz)->create(['question_type' => 'multiple_choice', 'points' => 10]);
    $essayQuestion = QuizQuestion::factory()->for($quiz)->create(['question_type' => 'essay', 'points' => 20]);

    $attempt = AssessmentAttempt::factory()->for($assessment)->create(['attempt_number' => 1, 'submitted_at' => now()]);
    AssessmentQuizAnswer::factory()->for($attempt, 'attempt')->for($mcQuestion, 'question')->create(['score' => 10]);
    AssessmentQuizAnswer::factory()->for($attempt, 'attempt')->for($essayQuestion, 'question')->create(['score' => null]);

    $service = app(QuizAttemptScoringService::class);

    expect($service->attemptTotal($attempt->id))->toBe(10.0);
    expect($service->hasPendingGrading($attempt->id))->toBeTrue();
});
