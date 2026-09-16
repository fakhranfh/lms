<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\RoleName;
use App\Livewire\Courses\GradebookIndex;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class GradebookIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]));
        $this->course = Course::factory()->for($this->school)->create();

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
    }

    public function test_user_without_permission_cannot_view_gradebook(): void
    {
        $this->actingAs($this->student);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_student_sees_own_final_score_card(): void
    {
        $this->student->givePermissionTo('gradebook.view');
        $this->actingAs($this->student);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Final Score')
            ->assertSee('THEORY: Personal Assignment');
    }

    public function test_teacher_sees_student_grid_with_link_to_detail_page(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee($this->student->name)
            ->assertSee(route('gradebook.show', [$this->course, $this->student]), false);
    }

    public function test_teacher_student_grid_is_paginated(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $extraStudents = User::factory()->forSchool($this->school)->count(15)->create();
        foreach ($extraStudents as $extraStudent) {
            CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $extraStudent->id]);
        }

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set('perPage', 12)
            ->assertViewHas('studentRows', fn ($studentRows) => $studentRows->count() === 12 && $studentRows->total() === 16)
            ->call('gotoPage', 2)
            ->assertViewHas('studentRows', fn ($studentRows) => $studentRows->count() === 4);
    }
}
