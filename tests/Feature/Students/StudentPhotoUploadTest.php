<?php

use App\Livewire\Students\StudentPhotoUpload;
use App\Services\R2StorageService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('bulk photo upload lists only students, filtered by search', function () {
    $actor = actingAsStudentManager(['students.edit']);
    $student = makeStudent($actor, ['name' => 'Findable Jane']);
    makeStudent($actor, ['name' => 'Someone Else']);

    Livewire::actingAs($actor)->test(StudentPhotoUpload::class)
        ->call('loadUsers')
        ->assertSee('Findable Jane')
        ->assertSee('Someone Else')
        ->set('search', 'Jane')
        ->assertSee('Findable Jane')
        ->assertDontSee('Someone Else');
});

test('user with students.edit can stage and save a bulk photo upload', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension not installed');
    }

    $actor = actingAsStudentManager(['students.edit']);
    $student = makeStudent($actor);

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/tmp-imports/photos/tmp-avatar.jpg');
        $mock->shouldReceive('promoteTempPhoto')
            ->once()
            ->with('https://r2.example.com/tmp-imports/photos/tmp-avatar.jpg', 'photos/students')
            ->andReturn('https://r2.example.com/photos/students/avatar.jpg');
    });

    $photo = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $component = Livewire::actingAs($actor)->test(StudentPhotoUpload::class)
        ->set("uploads.{$student->id}", $photo);

    expect($component->get('stagedPhotoUrls'))->toHaveKey((string) $student->id, 'https://r2.example.com/tmp-imports/photos/tmp-avatar.jpg');

    $component->call('save')
        ->assertSet('stagedPhotoUrls', []);

    expect($student->fresh()->profile_photo_path)->toBe('https://r2.example.com/photos/students/avatar.jpg');
});

test('re-uploading a photo before save discards the previous staged temp file', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension not installed');
    }

    $actor = actingAsStudentManager(['students.edit']);
    $student = makeStudent($actor);

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/tmp-imports/photos/tmp-first.jpg');
        $mock->shouldReceive('delete')
            ->once()
            ->with('https://r2.example.com/tmp-imports/photos/tmp-first.jpg')
            ->andReturn(true);
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/tmp-imports/photos/tmp-second.jpg');
    });

    $component = Livewire::actingAs($actor)->test(StudentPhotoUpload::class)
        ->set("uploads.{$student->id}", UploadedFile::fake()->image('first.jpg', 100, 100))
        ->set("uploads.{$student->id}", UploadedFile::fake()->image('second.jpg', 100, 100));

    expect($component->get('stagedPhotoUrls'))->toHaveKey((string) $student->id, 'https://r2.example.com/tmp-imports/photos/tmp-second.jpg');
});

test('staging a bulk photo upload requires students.edit permission', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor);

    Livewire::actingAs($actor)->test(StudentPhotoUpload::class)
        ->set("uploads.{$student->id}", UploadedFile::fake()->create('avatar.jpg', 100))
        ->assertForbidden();
});

test('saving a bulk photo upload requires students.edit permission', function () {
    $actor = actingAsStudentManager(['students.view']);

    Livewire::actingAs($actor)->test(StudentPhotoUpload::class)
        ->call('save')
        ->assertForbidden();
});
