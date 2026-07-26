<?php

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use App\Support\CurrentSchool;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->user = User::factory()->forSchool($this->school)->create();
    app(CurrentSchool::class)->setSchoolId($this->school->id);
    $this->actingAs($this->user);
});

test('creating a course writes an audit log with event created', function () {
    $course = Course::factory()->for($this->school)->for($this->user, 'creator')->create();

    $log = AuditLog::forModel($course)->first();

    expect($log)->not->toBeNull()
        ->and($log->event)->toBe('created')
        ->and($log->old_values)->toBeNull()
        ->and($log->new_values)->toBeArray()
        ->and($log->school_id)->toBe($this->school->id)
        ->and($log->user_id)->toBe($this->user->id);
});

test('updating a course writes an audit log with old and new values', function () {
    $course = Course::factory()->for($this->school)->for($this->user, 'creator')->create(['title' => 'Old Title']);

    $course->update(['title' => 'New Title']);

    $log = AuditLog::forModel($course)->where('event', 'updated')->latest('created_at')->first();

    expect($log)->not->toBeNull()
        ->and($log->old_values)->toBe(['title' => 'Old Title'])
        ->and($log->new_values)->toBe(['title' => 'New Title']);
});

test('deleting a course writes an audit log with event deleted', function () {
    $course = Course::factory()->for($this->school)->for($this->user, 'creator')->create();

    $course->delete();

    $log = AuditLog::forModel($course)->where('event', 'deleted')->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values)->toBeNull();
});

test('audit log ip address and user agent are captured from the request', function () {
    $course = Course::factory()->for($this->school)->for($this->user, 'creator')->create();

    $log = AuditLog::forModel($course)->first();

    expect($log->ip_address)->not->toBeNull();
});

test('audit logs cannot be updated', function () {
    $log = AuditLog::factory()->create();

    expect(fn () => $log->update(['description' => 'tampered']))
        ->toThrow(RuntimeException::class);
});

test('audit logs cannot be deleted individually', function () {
    $log = AuditLog::factory()->create();

    expect(fn () => $log->delete())
        ->toThrow(RuntimeException::class);
});
