<?php

namespace App\Dto;

class GradingRequest
{
    /**
     * @param  array<int, array<string, mixed>>  $rubric
     */
    public function __construct(
        public readonly string $submissionId,
        public readonly string $assignmentId,
        public readonly string $studentAnswer,
        public readonly array $rubric,
        public readonly float $maxScore,
        public readonly string $schoolId,
    ) {}

    /**
     * @return array{submission_id: string, assignment_id: string, student_answer: string, rubric: array<int, array<string, mixed>>, max_score: float, school_id: string}
     */
    public function toArray(): array
    {
        return [
            'submission_id' => $this->submissionId,
            'assignment_id' => $this->assignmentId,
            'student_answer' => $this->studentAnswer,
            'rubric' => $this->rubric,
            'max_score' => $this->maxScore,
            'school_id' => $this->schoolId,
        ];
    }
}
