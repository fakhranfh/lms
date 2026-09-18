<?php

use App\Models\User;
use App\Services\StudentSelectionService;

test('selection starts empty', function () {
    $actor = actingAsStudentManager(['students.view']);

    test()->actingAs($actor)
        ->getJson(route('students.selection.show'))
        ->assertOk()
        ->assertJson(['selected' => []]);

    app(StudentSelectionService::class)->clear($actor->id);
});

test('a student can be selected and unselected', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor);

    test()->actingAs($actor)
        ->postJson(route('students.selection.update'), ['id' => $student->id, 'checked' => true])
        ->assertOk()
        ->assertJson(['selected' => [$student->id]]);

    test()->actingAs($actor)
        ->postJson(route('students.selection.update'), ['id' => $student->id, 'checked' => false])
        ->assertOk()
        ->assertJson(['selected' => []]);
});

test('selection persists across requests, e.g. navigating between pagination pages', function () {
    $actor = actingAsStudentManager(['students.view']);
    $studentOne = makeStudent($actor);
    $studentTwo = makeStudent($actor);

    test()->actingAs($actor)->postJson(route('students.selection.update'), ['id' => $studentOne->id, 'checked' => true])->assertOk();
    test()->actingAs($actor)->postJson(route('students.selection.update'), ['id' => $studentTwo->id, 'checked' => true])->assertOk();

    $response = test()->actingAs($actor)->getJson(route('students.selection.show'))->assertOk();

    expect($response->json('selected'))->toEqualCanonicalizing([$studentOne->id, $studentTwo->id]);

    app(StudentSelectionService::class)->clear($actor->id);
});

test('a page of students can be selected in one batch call', function () {
    $actor = actingAsStudentManager(['students.view']);
    $studentOne = makeStudent($actor);
    $studentTwo = makeStudent($actor);

    test()->actingAs($actor)
        ->postJson(route('students.selection.update-many'), ['ids' => [$studentOne->id, $studentTwo->id], 'checked' => true])
        ->assertOk();

    $response = test()->actingAs($actor)->getJson(route('students.selection.show'))->assertOk();
    expect($response->json('selected'))->toEqualCanonicalizing([$studentOne->id, $studentTwo->id]);

    test()->actingAs($actor)
        ->postJson(route('students.selection.update-many'), ['ids' => [$studentOne->id, $studentTwo->id], 'checked' => false])
        ->assertOk()
        ->assertJson(['selected' => []]);
});

test('selection can be cleared', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor);

    test()->actingAs($actor)->postJson(route('students.selection.update'), ['id' => $student->id, 'checked' => true])->assertOk();

    test()->actingAs($actor)
        ->deleteJson(route('students.selection.clear'))
        ->assertOk()
        ->assertJson(['selected' => []]);
});

test('selection endpoints require students.view permission', function () {
    $actor = User::factory()->create();

    test()->actingAs($actor)
        ->getJson(route('students.selection.show'))
        ->assertForbidden();
});

test('selection is scoped per user', function () {
    $actorOne = actingAsStudentManager(['students.view']);
    $actorTwo = actingAsStudentManager(['students.view']);
    $student = makeStudent($actorOne);

    test()->actingAs($actorOne)->postJson(route('students.selection.update'), ['id' => $student->id, 'checked' => true])->assertOk();

    test()->actingAs($actorTwo)
        ->getJson(route('students.selection.show'))
        ->assertOk()
        ->assertJson(['selected' => []]);

    app(StudentSelectionService::class)->clear($actorOne->id);
});
