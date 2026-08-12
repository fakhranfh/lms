<?php

use App\Models\QuizInstruction;
use App\Models\School;
use App\Models\User;
use Database\Seeders\QuizInstructionSeeder;

test('quiz instruction seeder creates the global instruction record', function () {
    (new QuizInstructionSeeder)->run();

    expect(QuizInstruction::count())->toBe(1)
        ->and(QuizInstruction::first()->content)->not->toBeEmpty();
});

test('quiz instruction seeder does not duplicate an existing record', function () {
    $school = School::factory()->create();
    $admin = User::factory()->forSchool($school)->create();
    QuizInstruction::factory()->create(['content' => 'Existing instructions', 'updated_by' => $admin->id]);

    (new QuizInstructionSeeder)->run();

    expect(QuizInstruction::count())->toBe(1)
        ->and(QuizInstruction::first()->content)->toBe('Existing instructions');
});
