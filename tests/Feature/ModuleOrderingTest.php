<?php

use App\Models\Course;
use App\Models\Module;

test('module moveDown swaps order with next module', function () {
    $course = Course::factory()->create();
    $first = Module::factory()->for($course)->create(['order' => 1]);
    $second = Module::factory()->for($course)->create(['order' => 2]);

    $first->moveDown();

    expect($first->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(1);
});

test('module moveUp swaps order with previous module', function () {
    $course = Course::factory()->create();
    $first = Module::factory()->for($course)->create(['order' => 1]);
    $second = Module::factory()->for($course)->create(['order' => 2]);

    $second->moveUp();

    expect($first->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(1);
});

test('module moveDown can be called repeatedly without corrupting order', function () {
    $course = Course::factory()->create();
    $modules = Module::factory()->for($course)->count(3)->sequence(
        ['order' => 1],
        ['order' => 2],
        ['order' => 3],
    )->create();

    $modules[0]->moveDown();
    $modules[0]->refresh()->moveDown();

    $orders = $course->modules()->orderBy('order')->pluck('order')->all();

    expect($orders)->toBe([1, 2, 3]);
});

test('module moveUp on the bottom item swaps with the adjacent module only', function () {
    $course = Course::factory()->create();
    $first = Module::factory()->for($course)->create(['order' => 1]);
    $second = Module::factory()->for($course)->create(['order' => 2]);
    $third = Module::factory()->for($course)->create(['order' => 3]);

    $third->moveUp();

    expect($first->fresh()->order)->toBe(1)
        ->and($third->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(3);
});
