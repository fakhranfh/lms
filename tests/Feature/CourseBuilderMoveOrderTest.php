<?php

use App\Livewire\Courses\CourseBuilder;
use App\Models\Course;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;

test('clicking move up on bottom module moves it up by one position only', function () {
    $school = School::factory()->create();
    $instructor = User::factory()->for($school)->create();
    $instructor->givePermissionTo(['courses.view', 'modules.edit']);
    $this->actingAs($instructor);

    $course = Course::factory()->for($school)->create();
    $first = Module::factory()->for($course)->create(['title' => 'First', 'order' => 1]);
    $second = Module::factory()->for($course)->create(['title' => 'Second', 'order' => 2]);
    $third = Module::factory()->for($course)->create(['title' => 'Third (bottom)', 'order' => 3]);

    Livewire::test(CourseBuilder::class, ['course' => $course])
        ->call('moveModuleUp', $third->id);

    expect($first->fresh()->order)->toBe(1)
        ->and($third->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(3);
});
