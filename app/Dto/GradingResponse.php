<?php

namespace App\Dto;

class GradingResponse
{
    /**
     * @param  array<int, array<string, mixed>>|null  $feedback
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?float $score = null,
        public readonly ?array $feedback = null,
        public readonly ?string $errorMessage = null,
        public readonly int $retryCount = 0,
        public readonly ?string $timestamp = null,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $feedback
     */
    public static function success(float $score, array $feedback): self
    {
        return new self(
            success: true,
            score: $score,
            feedback: $feedback,
            timestamp: now()->toIso8601String(),
        );
    }

    public static function failure(string $errorMessage, int $retryCount = 0): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            retryCount: $retryCount,
            timestamp: now()->toIso8601String(),
        );
    }

    /**
     * @return array{success: bool, score: float|null, feedback: array<int, array<string, mixed>>|null, error_message: string|null, retry_count: int, timestamp: string|null}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'error_message' => $this->errorMessage,
            'retry_count' => $this->retryCount,
            'timestamp' => $this->timestamp,
        ];
    }
}
