<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\QuizInstructionEdit;
use App\Models\Course;
use App\Models\QuizInstruction;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class QuizInstructionEditTest extends TestCase
{
    public function test_user_cannot_access_without_permission(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->forSchool($school)->create();
        $this->actingAs($teacher);

        Livewire::test(QuizInstructionEdit::class)->assertStatus(403);
    }

    public function test_first_save_creates_instruction(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->forSchool($school)->create();
        $teacher->givePermissionTo('assessment.edit');
        $this->actingAs($teacher);

        Livewire::test(QuizInstructionEdit::class)
            ->assertSet('content', '')
            ->set('content', 'Read carefully before starting.')
            ->call('save');

        $this->assertDatabaseHas('quiz_instructions', [
            'content' => 'Read carefully before starting.',
            'updated_by' => $teacher->id,
        ]);
        $this->assertEquals(1, QuizInstruction::count());
    }

    public function test_second_save_updates_existing_record(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->forSchool($school)->create();
        $teacher->givePermissionTo('assessment.edit');
        $this->actingAs($teacher);

        Livewire::test(QuizInstructionEdit::class)
            ->set('content', 'First version')
            ->call('save');

        Livewire::test(QuizInstructionEdit::class)
            ->assertSet('content', 'First version')
            ->set('content', 'Second version')
            ->call('save');

        $this->assertEquals(1, QuizInstruction::count());
        $this->assertDatabaseHas('quiz_instructions', ['content' => 'Second version']);
    }

    public function test_instruction_is_global_across_courses(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $teacherA = User::factory()->forSchool($schoolA)->create();
        $teacherB = User::factory()->forSchool($schoolB)->create();
        $teacherA->givePermissionTo('assessment.edit');
        $teacherB->givePermissionTo('assessment.edit');

        Course::factory()->for($schoolA)->create();
        Course::factory()->for($schoolB)->create();

        $this->actingAs($teacherA);
        Livewire::test(QuizInstructionEdit::class)
            ->set('content', 'Shared instructions')
            ->call('save');

        $this->actingAs($teacherB);
        Livewire::test(QuizInstructionEdit::class)
            ->assertSet('content', 'Shared instructions');
    }
}
