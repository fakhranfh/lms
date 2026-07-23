<?php

use App\Services\AiGradingProviderFactory;
use App\Services\DeepSeekService;
use App\Services\GeminiService;

beforeEach(function () {
    $this->factory = new AiGradingProviderFactory;
});

test('make defaults to gemini provider from config', function () {
    config(['services.ai_grading.provider' => 'gemini']);

    expect($this->factory->make())->toBeInstanceOf(GeminiService::class);
});

test('make resolves deepseek provider from config', function () {
    config(['services.ai_grading.provider' => 'deepseek']);

    expect($this->factory->make())->toBeInstanceOf(DeepSeekService::class);
});

test('make resolves provider by explicit argument regardless of config', function () {
    config(['services.ai_grading.provider' => 'gemini']);

    expect($this->factory->make('deepseek'))->toBeInstanceOf(DeepSeekService::class);
});

test('make throws for unknown provider', function () {
    $this->factory->make('unknown-provider');
})->throws(InvalidArgumentException::class);
