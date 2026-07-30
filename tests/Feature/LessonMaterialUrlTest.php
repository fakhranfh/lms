<?php

use App\Models\LessonMaterial;
use App\Services\R2StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('file url is rebuilt from the stored key using current r2 config', function () {
    config(['services.r2.custom_domain' => 'https://cdn.example.com']);
    app()->forgetInstance(R2StorageService::class);

    $material = LessonMaterial::factory()->create([
        'file_path' => 'lessons/abc/materials/hash-video.mp4',
        'file_url' => 'https://stale-domain.example.com/lessons/abc/materials/hash-video.mp4',
    ]);

    expect($material->file_url)->toBe('https://cdn.example.com/lessons/abc/materials/hash-video.mp4');
});
