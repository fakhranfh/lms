<?php

use App\Enums\RoleInCourse;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\CoursePersonService;
use App\Services\GroupService;

test('course person service filters students and teachers for a course', function () {
    $course = Course::factory()->create();
    CoursePerson::factory()->for($course)->create(['role_in_course' => RoleInCourse::Teacher]);
    CoursePerson::factory()->for($course)->count(2)->create(['role_in_course' => RoleInCourse::Student]);

    $service = app(CoursePersonService::class);

    expect($service->studentsForCourse($course->id))->toHaveCount(2);
    expect($service->teachersForCourse($course->id))->toHaveCount(1);
});

test('group belongs to a course and has members', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->for($course)->create();
    $user = User::factory()->create();
    GroupMember::factory()->for($group)->for($user)->create();

    expect(app(GroupService::class)->forCourse($course->id))->toHaveCount(1);
    expect($group->members()->count())->toBe(1);
});

test('force deleting a course cascades to people and groups', function () {
    $course = Course::factory()->create();
    $person = CoursePerson::factory()->for($course)->create();
    $group = Group::factory()->for($course)->create();

    $course->forceDelete();

    expect(CoursePerson::find($person->id))->toBeNull();
    expect(Group::find($group->id))->toBeNull();
});
