<?php

use App\Models\LessonMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('file url is rebuilt from the stored key using current r2 config', function () {
    $material = LessonMaterial::factory()->create([
        'file_path' => 'lessons/abc/materials/hash-video.mp4',
        'file_url' => 'https://stale-domain.example.com/lessons/abc/materials/hash-video.mp4',
    ]);

    expect($material->file_url)->toBe(config('services.r2.custom_domain').'/lessons/abc/materials/hash-video.mp4');
});
