<?php

namespace App\Services;

use App\Services\AiGrading\AbstractOpenAiCompatibleProvider;

class DeepSeekService extends AbstractOpenAiCompatibleProvider
{
    public function __construct()
    {
        parent::__construct(
            providerName: 'DeepSeek',
            baseUrl: (string) config('services.deepseek.base_url'),
            apiKey: (string) config('services.deepseek.api_key'),
            model: (string) config('services.deepseek.model'),
            timeout: (int) config('services.deepseek.timeout', 30),
        );
    }
}
