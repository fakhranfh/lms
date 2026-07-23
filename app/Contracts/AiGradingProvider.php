<?php

namespace App\Contracts;

use App\Models\Assignment;

interface AiGradingProvider
{
    /**
     * Grade an essay against a rubric via the provider's chat completions API.
     *
     * @param  array<int, array<string, mixed>>  $rubric
     * @return array{success: bool, score?: float, feedback?: array<int, array<string, mixed>>, raw_response?: string, error?: string}
     */
    public function gradeEssay(string $essay, array $rubric, string $prompt): array;

    public function buildGradingPrompt(Assignment $assignment, string $essay): string;

    /**
     * @return array{score: float, feedback: array<int, array<string, mixed>>}
     */
    public function parseGradingResponse(string $responseText): array;
}
