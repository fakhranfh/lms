<?php

namespace App\Services;

use App\Services\AiGrading\AbstractOpenAiCompatibleProvider;

class GeminiService extends AbstractOpenAiCompatibleProvider
{
    public function __construct()
    {
        parent::__construct(
            providerName: 'Gemini',
            baseUrl: (string) config('services.gemini.base_url'),
            apiKey: (string) config('services.gemini.api_key'),
            model: (string) config('services.gemini.model'),
            timeout: (int) config('services.gemini.timeout', 30),
        );
    }
}
