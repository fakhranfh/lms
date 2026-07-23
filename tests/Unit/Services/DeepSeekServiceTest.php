<?php

use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.deepseek.api_key' => 'test-key',
        'services.deepseek.model' => 'deepseek-chat',
        'services.deepseek.base_url' => 'https://api.deepseek.com',
        'services.deepseek.timeout' => 30,
    ]);

    $this->service = new DeepSeekService;

    $this->rubric = [
        ['item' => 'Clarity', 'points' => 20],
        ['item' => 'Evidence', 'points' => 30],
    ];
});

test('gradeEssay returns structured success response for valid JSON', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'score' => 85.5,
                    'feedback' => [
                        ['rubric_item' => 'Clarity', 'points_earned' => 18, 'points_max' => 20, 'comment' => 'Well organized.'],
                    ],
                    'summary' => 'Strong essay.',
                    'suggestions' => ['Add more examples.'],
                ])]],
            ],
        ]),
    ]);

    $result = $this->service->gradeEssay('My essay text', $this->rubric, 'Discuss the topic.');

    expect($result['success'])->toBeTrue();
    expect($result['score'])->toBe(85.5);
    expect($result['feedback'])->toBeArray()->toHaveCount(1);
});

test('gradeEssay handles markdown-wrapped JSON responses', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => "```json\n".json_encode([
                    'score' => 70,
                    'feedback' => [],
                ])."\n```"]],
            ],
        ]),
    ]);

    $result = $this->service->gradeEssay('My essay text', $this->rubric, 'Discuss the topic.');

    expect($result['success'])->toBeTrue();
    expect($result['score'])->toBe(70.0);
});

test('gradeEssay returns structured error on API 401', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $result = $this->service->gradeEssay('My essay text', $this->rubric, 'Discuss the topic.');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->not->toBeEmpty();
});

test('gradeEssay returns structured error on API 500', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response(['error' => 'Server error'], 500),
    ]);

    $result = $this->service->gradeEssay('My essay text', $this->rubric, 'Discuss the topic.');

    expect($result['success'])->toBeFalse();
});

test('gradeEssay returns structured error when response has malformed JSON', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'not valid json at all']],
            ],
        ]),
    ]);

    $result = $this->service->gradeEssay('My essay text', $this->rubric, 'Discuss the topic.');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('No JSON object found');
});

test('parseGradingResponse extracts and validates plain JSON', function () {
    $json = json_encode([
        'score' => 92,
        'feedback' => [
            ['rubric_item' => 'Argument', 'points_earned' => 28, 'points_max' => 30, 'comment' => 'Coherent.'],
        ],
    ]);

    $parsed = $this->service->parseGradingResponse($json);

    expect($parsed['score'])->toBe(92.0);
    expect($parsed['feedback'])->toHaveCount(1);
});

test('parseGradingResponse throws when structure is invalid', function () {
    $this->service->parseGradingResponse(json_encode(['foo' => 'bar']));
})->throws(RuntimeException::class);
