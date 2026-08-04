<?php

use App\Enums\FinalExamType;
use App\Models\Assessment;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Services\QuizService;

test('quiz belongs to an assessment and has questions with options', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create();
    $question = QuizQuestion::factory()->for($quiz)->create(['order' => 1]);
    QuizQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true, 'order' => 1]);
    QuizQuestionOption::factory()->for($question, 'question')->create(['is_correct' => false, 'order' => 2]);

    expect(app(QuizService::class)->findByAssessment($assessment->id)->id)->toBe($quiz->id);
    expect($quiz->questions()->count())->toBe(1);
    expect($question->options()->count())->toBe(2);
});

test('final exam is scoped to a period covering multiple sessions', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_final_exam']);
    $period = Period::factory()->for($assessment->course)->create();

    $finalExam = FinalExam::factory()
        ->for($assessment)
        ->for($period)
        ->create(['exam_type' => FinalExamType::OpenBook]);

    expect($finalExam->period->id)->toBe($period->id);
    expect($finalExam->assessment->id)->toBe($assessment->id);
});

test('deleting an assessment cascades to its quiz', function () {
    $assessment = Assessment::factory()->create(['type' => 'theory_quiz']);
    $quiz = Quiz::factory()->for($assessment)->create();

    $assessment->delete();

    expect(Quiz::find($quiz->id))->toBeNull();
});
