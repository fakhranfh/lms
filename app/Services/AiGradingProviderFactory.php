<?php

namespace App\Services;

use App\Contracts\AiGradingProvider;
use InvalidArgumentException;

class AiGradingProviderFactory
{
    public function make(?string $provider = null): AiGradingProvider
    {
        $provider ??= (string) config('services.ai_grading.provider', 'gemini');

        return match ($provider) {
            'gemini' => new GeminiService,
            'deepseek' => new DeepSeekService,
            default => throw new InvalidArgumentException("Unknown AI grading provider: {$provider}"),
        };
    }
}
