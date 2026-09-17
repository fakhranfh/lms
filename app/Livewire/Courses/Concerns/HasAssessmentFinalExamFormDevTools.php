<?php

namespace App\Livewire\Courses\Concerns;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentType;
use App\Support\AssessmentTypeLabel;

trait HasAssessmentFinalExamFormDevTools
{
    /**
     * Dev-only: fills the form with fake data so the UI can be exercised
     * without manually typing every field. Content deliberately differs
     * from AssessmentIndex::generateFinalExam()'s wording so the two dev
     * tools don't produce identical-looking exams. Each exam type gets its
     * own instructions text and question shape — take_home collapses to the
     * single forced essay question that shape actually allows (see
     * toggleFinalExamType in resources/js/assessment-form.js), the other two
     * get the full 15 MC + 5 essay set.
     */
    public function devAutofill(string $examType): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);
        abort_unless(in_array($examType, ['open_book', 'closed_book', 'take_home'], true), 422);

        $this->title = AssessmentTypeLabel::forType(AssessmentType::TheoryFinalExam).' - Comprehensive Review';
        $this->weight = (string) AssessmentType::TheoryFinalExam->defaultWeight();
        $this->startDate = now()->addWeek()->setTime(8, 0)->format('Y-m-d\TH:i');
        $this->endDate = now()->addWeek()->setTime(10, 0)->format('Y-m-d\TH:i');
        $this->status = 'draft';
        $this->examType = $examType;
        $this->instructions = match ($examType) {
            'open_book' => '<p>You may consult your notes, textbooks, and any written or digital materials while answering. Collaboration with other students is not permitted.</p>',
            'take_home' => '<p>Submit your response before the exam window closes. Cite any external sources you reference in your answer.</p>',
            default => '<p>Answer every question independently. No collaboration is permitted during this exam.</p>',
        };
        $this->dispatch('rich-text-set-content', id: 'final-exam-instructions', value: $this->instructions);

        if ($examType === 'take_home') {
            $this->questions = [[
                'id' => null,
                'description' => '<p>Design a caching strategy for a high-traffic e-commerce product catalog. Describe what you would cache, how you would invalidate stale entries, and how you would measure whether the strategy is working.</p>',
                'questionType' => AssessmentQuestionType::Essay->value,
                'points' => '100',
                'order' => 1,
                'options' => [],
            ]];

            $this->dispatch('rich-text-set-content', id: 'question-0', value: $this->questions[0]['description']);

            return;
        }

        $mcContent = [
            ['description' => 'Which data structure provides O(1) average-case lookup by key?', 'options' => ['Hash table', 'Linked list', 'Binary search tree', 'Array']],
            ['description' => 'In relational database design, what does the term "normalization" primarily aim to reduce?', 'options' => ['Data redundancy', 'Query latency', 'Index count', 'Table count']],
            ['description' => 'Which HTTP status code indicates a successful resource creation?', 'options' => ['201 Created', '200 OK', '204 No Content', '301 Moved Permanently']],
            ['description' => 'What is the time complexity of binary search on a sorted array of size n?', 'options' => ['O(log n)', 'O(n)', 'O(n log n)', 'O(1)']],
            ['description' => 'Which principle states that a class should have only one reason to change?', 'options' => ['Single Responsibility Principle', 'Open/Closed Principle', 'Liskov Substitution Principle', 'Interface Segregation Principle']],
            ['description' => 'What does ACID stand for in the context of database transactions?', 'options' => ['Atomicity, Consistency, Isolation, Durability', 'Availability, Consistency, Isolation, Durability', 'Atomicity, Concurrency, Isolation, Durability', 'Atomicity, Consistency, Integrity, Durability']],
            ['description' => 'Which of the following best describes a race condition?', 'options' => ['Two or more threads accessing shared data with an unsynchronized outcome', 'A CPU scheduling algorithm', 'A network congestion pattern', 'A compiler optimization']],
            ['description' => 'What is the primary purpose of a load balancer in a distributed system?', 'options' => ['Distributing incoming requests across multiple servers', 'Encrypting traffic between clients and servers', 'Caching database query results', 'Compressing HTTP responses']],
            ['description' => 'Which sorting algorithm has the best average-case time complexity?', 'options' => ['Quicksort', 'Bubble sort', 'Insertion sort', 'Selection sort']],
            ['description' => 'What is the main benefit of using dependency injection in software design?', 'options' => ['Reduced coupling between components', 'Faster runtime execution', 'Smaller binary size', 'Automatic memory management']],
            ['description' => 'In version control, what does a "merge conflict" indicate?', 'options' => ['Two branches changed the same lines differently', 'A missing commit message', 'A corrupted repository', 'An expired access token']],
            ['description' => 'Which layer of the OSI model is responsible for routing packets between networks?', 'options' => ['Network layer', 'Transport layer', 'Data link layer', 'Application layer']],
            ['description' => 'What is the purpose of an index in a database table?', 'options' => ['Speeding up data retrieval at the cost of write overhead', 'Enforcing foreign key constraints', 'Storing backup copies of rows', 'Compressing table storage']],
            ['description' => 'Which testing approach verifies that individual units of code work in isolation?', 'options' => ['Unit testing', 'Integration testing', 'End-to-end testing', 'Smoke testing']],
            ['description' => 'What does the term "idempotent" mean for an HTTP method?', 'options' => ['Repeating the same request produces the same result', 'The request always succeeds', 'The request is cached by default', 'The request requires authentication']],
        ];

        $essayContent = [
            'Explain the trade-offs between horizontal and vertical scaling for a growing web application.',
            'Describe how you would design a database schema for a course enrollment system, including the key entities and relationships.',
            'Discuss the advantages and disadvantages of using microservices compared to a monolithic architecture.',
            'Explain how caching can improve application performance and describe a scenario where caching could introduce bugs.',
            'Walk through the steps you would take to diagnose a slow API endpoint in production.',
        ];

        $questions = collect($mcContent)->values()->map(fn ($question, $index) => [
            'id' => null,
            'description' => '<p>'.$question['description'].'</p>',
            'questionType' => AssessmentQuestionType::MultipleChoice->value,
            'points' => '',
            'order' => $index + 1,
            'options' => collect($question['options'])->values()->map(fn ($label, $optionIndex) => [
                'id' => null,
                'label' => $label,
                'isCorrect' => $optionIndex === 0,
                'order' => $optionIndex + 1,
            ])->all(),
        ])->all();

        $offset = count($questions);
        $essayQuestions = collect($essayContent)->values()->map(fn ($description, $index) => [
            'id' => null,
            'description' => '<p>'.$description.'</p>',
            'questionType' => AssessmentQuestionType::Essay->value,
            'points' => '20',
            'order' => $offset + $index + 1,
            'options' => [],
        ])->all();

        $this->questions = [...$questions, ...$essayQuestions];

        // Question descriptions run wire:ignore, so their DOM is silent to
        // property changes; they only refresh when told to via this event.
        foreach ($this->questions as $index => $question) {
            $this->dispatch('rich-text-set-content', id: "question-{$index}", value: $question['description']);
        }
    }
}
