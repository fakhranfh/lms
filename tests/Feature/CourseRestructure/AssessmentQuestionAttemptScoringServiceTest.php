<?php

use App\Enums\QuizScoringMethod;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentQuestionAnswer;
use App\Models\AssessmentQuestionOption;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AssessmentQuestionAttemptScoringService;

test('objective answer is scored against the correct option', function () {
    $question = AssessmentQuestion::factory()->create(['question_type' => 'multiple_choice', 'points' => 10]);
    $correct = AssessmentQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true]);
    $wrong = AssessmentQuestionOption::factory()->for($question, 'question')->create(['is_correct' => false]);
    $question->load('options');

    $service = app(AssessmentQuestionAttemptScoringService::class);

    expect($service->scoreObjectiveAnswer($question, $correct->id))->toBe(10.0);
    expect($service->scoreObjectiveAnswer($question, $wrong->id))->toBe(0.0);
    expect($service->scoreObjectiveAnswer($question, null))->toBe(0.0);
});

test('true/false questions are scored objectively like multiple choice', function () {
    $question = AssessmentQuestion::factory()->create(['question_type' => 'true_false', 'points' => 5]);
    $correct = AssessmentQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true]);
    $question->load('options');

    $service = app(AssessmentQuestionAttemptScoringService::class);

    expect($service->scoreObjectiveAnswer($question, $correct->id))->toBe(5.0);
});

test('essay questions are not auto-scored', function () {
    $question = AssessmentQuestion::factory()->create(['question_type' => 'essay', 'points' => 10]);

    $service = app(AssessmentQuestionAttemptScoringService::class);

    expect($service->scoreObjectiveAnswer($question, null))->toBeNull();
});

test('short answer questions are not auto-scored', function () {
    $question = AssessmentQuestion::factory()->create(['question_type' => 'short_answer', 'points' => 10]);

    $service = app(AssessmentQuestionAttemptScoringService::class);

    expect($service->scoreObjectiveAnswer($question, null))->toBeNull();
});

test('scoring method highest picks the best submitted attempt', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create(['scoring_method' => QuizScoringMethod::Highest]);
    $question = AssessmentQuestion::factory()->for($assessment)->create(['points' => 10]);
    $user = User::factory()->create();

    $attempt1 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 1, 'submitted_at' => now()->subHour()]);
    AssessmentQuestionAnswer::factory()->for($attempt1, 'attempt')->for($question, 'question')->create(['score' => 3]);

    $attempt2 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 2, 'submitted_at' => now()]);
    AssessmentQuestionAnswer::factory()->for($attempt2, 'attempt')->for($question, 'question')->create(['score' => 10]);

    $service = app(AssessmentQuestionAttemptScoringService::class);
    $counted = $service->recomputeForUser($quiz, $assessment->id, $user->id);

    expect($counted->id)->toBe($attempt2->id);
    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt2->id, 'score' => 10]);
});

test('scoring method latest picks the most recently submitted attempt', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create(['scoring_method' => QuizScoringMethod::Latest]);
    $question = AssessmentQuestion::factory()->for($assessment)->create(['points' => 10]);
    $user = User::factory()->create();

    $attempt1 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 1, 'submitted_at' => now()->subHour()]);
    AssessmentQuestionAnswer::factory()->for($attempt1, 'attempt')->for($question, 'question')->create(['score' => 10]);

    $attempt2 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 2, 'submitted_at' => now()]);
    AssessmentQuestionAnswer::factory()->for($attempt2, 'attempt')->for($question, 'question')->create(['score' => 4]);

    $service = app(AssessmentQuestionAttemptScoringService::class);
    $counted = $service->recomputeForUser($quiz, $assessment->id, $user->id);

    expect($counted->id)->toBe($attempt2->id);
    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt2->id, 'score' => 4]);
});

test('scoring method average computes the mean total across attempts', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create(['scoring_method' => QuizScoringMethod::Average]);
    $question = AssessmentQuestion::factory()->for($assessment)->create(['points' => 10]);
    $user = User::factory()->create();

    $attempt1 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 1, 'submitted_at' => now()->subHour()]);
    AssessmentQuestionAnswer::factory()->for($attempt1, 'attempt')->for($question, 'question')->create(['score' => 4]);

    $attempt2 = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $user->id, 'attempt_number' => 2, 'submitted_at' => now()]);
    AssessmentQuestionAnswer::factory()->for($attempt2, 'attempt')->for($question, 'question')->create(['score' => 8]);

    $service = app(AssessmentQuestionAttemptScoringService::class);
    $service->recomputeForUser($quiz, $assessment->id, $user->id);

    $this->assertDatabaseHas('assessment_scores', ['score' => 6]);
});

test('partial total treats an ungraded essay answer as zero', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create();
    $mcQuestion = AssessmentQuestion::factory()->for($assessment)->create(['question_type' => 'multiple_choice', 'points' => 10]);
    $essayQuestion = AssessmentQuestion::factory()->for($assessment)->create(['question_type' => 'essay', 'points' => 20]);

    $attempt = AssessmentAttempt::factory()->for($assessment)->create(['attempt_number' => 1, 'submitted_at' => now()]);
    AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($mcQuestion, 'question')->create(['score' => 10]);
    AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($essayQuestion, 'question')->create(['score' => null]);

    $service = app(AssessmentQuestionAttemptScoringService::class);

    expect($service->attemptTotal($attempt->id))->toBe(10.0);
    expect($service->hasPendingGrading($attempt->id))->toBeTrue();
});
