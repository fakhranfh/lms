<?php

namespace App\Livewire\Courses\Concerns;

use App\Enums\AssessmentType;
use App\Services\SessionService;
use App\Support\AssessmentTypeLabel;

trait HasAssessmentQuizFormDevTools
{
    /**
     * Dev-only: fills the form with fake data so the UI can be exercised
     * without manually typing every field.
     */
    public function devAutofill(SessionService $sessionService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $this->title = AssessmentTypeLabel::forType(AssessmentType::TheoryQuiz).' - Week '.random_int(1, 14).' Quiz';
        $this->weight = (string) AssessmentType::TheoryQuiz->defaultWeight();
        $firstSession = $sessionService->forCourse($this->course->id)->first();
        $this->sessionId = $firstSession->id ?? '';
        $this->status = 'draft';
        $this->totalAttempts = '3';
        $this->scoringMethod = 'highest';
        $this->timeLimitPerAttempt = '30';

        $questionContent = [
            ['description' => 'What is the primary purpose of the concept covered this week?', 'options' => ['A correct explanation', 'An unrelated definition', 'A common misconception']],
            ['description' => 'Which of the following best describes the technique discussed in class?', 'options' => ['The correct technique', 'A similar but incorrect technique', 'An unrelated technique']],
            ['description' => 'Given the example from the lecture, what would be the expected outcome?', 'options' => ['The correct outcome', 'A plausible but wrong outcome', 'An unrelated outcome']],
        ];

        $this->questions = collect($questionContent)->values()->map(fn ($question, $index) => [
            'id' => null,
            'description' => '<p>'.$question['description'].'</p>',
            'order' => $index + 1,
            'options' => collect($question['options'])->values()->map(fn ($label, $optionIndex) => [
                'id' => null,
                'label' => $label,
                'isCorrect' => $optionIndex === 0,
                'order' => $optionIndex + 1,
            ])->all(),
        ])->all();

        // Question descriptions run wire:ignore, so their DOM is silent to
        // property changes; they only refresh when told to via this event.
        foreach ($this->questions as $index => $question) {
            $this->dispatch('rich-text-set-content', id: "question-{$index}", value: $question['description']);
        }
    }
}
