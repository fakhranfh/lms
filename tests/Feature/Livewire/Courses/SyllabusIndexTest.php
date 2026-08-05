<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\SyllabusIndex;
use App\Models\Course;
use App\Models\School;
use App\Models\Syllabus;
use App\Models\SyllabusClassPolicy;
use App\Models\SyllabusEvaluation;
use App\Models\SyllabusEvaluationActivity;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class SyllabusIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $this->actingAs($this->teacher);
    }

    public function test_user_cannot_access_index_without_permission(): void
    {
        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_shows_empty_state_cta_when_no_syllabus(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertSee('No syllabus has been created')
            ->assertSee('Create Syllabus');
    }

    public function test_renders_full_syllabus_with_class_policies_and_evaluation_totals(): void
    {
        $this->teacher->givePermissionTo('syllabus.view');

        $syllabus = Syllabus::factory()->for($this->course)->create([
            'course_description' => 'A great course',
        ]);

        SyllabusClassPolicy::factory()->for($syllabus)->create(['scope' => 'f2f_video', 'content' => 'F2F policy text']);
        SyllabusClassPolicy::factory()->for($syllabus)->create(['scope' => 'online', 'content' => 'Online policy text']);

        $evaluation = SyllabusEvaluation::factory()->for($syllabus)->create(['class_type' => 'LEC']);
        SyllabusEvaluationActivity::factory()->for($evaluation, 'evaluation')->create(['activity' => 'Quiz 1', 'weight' => 40]);
        SyllabusEvaluationActivity::factory()->for($evaluation, 'evaluation')->create(['activity' => 'Quiz 2', 'weight' => 60]);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertSee('A great course')
            ->assertSee('F2F policy text')
            ->assertSee('Online policy text')
            ->assertSee('Quiz 1')
            ->assertSee('Total: 100%');
    }

    public function test_edit_button_visible_only_when_can_edit(): void
    {
        $this->teacher->givePermissionTo('syllabus.view');
        Syllabus::factory()->for($this->course)->create();

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertDontSee('Edit Syllabus');

        $this->teacher->givePermissionTo('syllabus.edit');

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertSee('Edit Syllabus');
    }

    public function test_user_cannot_view_syllabus_of_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo('syllabus.view');

        Livewire::test(SyllabusIndex::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }
}
