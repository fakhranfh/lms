<?php

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\School;
use App\Models\User;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->user = User::factory()->forSchool($this->school)->create();
    $this->actingAs($this->user);
});

test('updating without real changes does not create an audit log', function () {
    $course = Course::factory()->for($this->school)->for($this->user, 'creator')->create();

    $countBefore = AuditLog::forModel($course)->count();

    $course->save();

    expect(AuditLog::forModel($course)->count())->toBe($countBefore);
});

test('touching only updated_at does not trigger an audit log', function () {
    $course = Course::factory()->for($this->school)->for($this->user, 'creator')->create();

    $countBefore = AuditLog::forModel($course)->count();

    $course->touch();

    expect(AuditLog::forModel($course)->count())->toBe($countBefore);
});

test('multiple field changes in a single update produce a single audit log', function () {
    $course = Course::factory()->for($this->school)->for($this->user, 'creator')->create(['title' => 'A', 'description' => 'B']);

    $countBefore = AuditLog::forModel($course)->where('event', 'updated')->count();

    $course->update(['title' => 'C', 'description' => 'D']);

    $log = AuditLog::forModel($course)->where('event', 'updated')->latest('created_at')->first();

    expect(AuditLog::forModel($course)->where('event', 'updated')->count())->toBe($countBefore + 1)
        ->and($log->new_values)->toMatchArray(['title' => 'C', 'description' => 'D']);
});
