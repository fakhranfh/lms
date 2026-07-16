<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\ModuleForm;
use App\Models\Course;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleFormTest extends TestCase
{
    private School $school;

    private User $instructor;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->instructor = User::factory()
            ->for($this->school)
            ->create();
        $this->course = Course::factory()
            ->for($this->school)
            ->create();

        $this->actingAs($this->instructor);
    }

    public function test_create_module_component_renders(): void
    {
        $this->instructor->givePermissionTo('modules.create');

        Livewire::test(ModuleForm::class, ['course' => $this->course])
            ->assertStatus(200)
            ->assertSee('Create Module');
    }

    public function test_edit_module_component_renders(): void
    {
        $module = Module::factory()
            ->for($this->course)
            ->create();

        $this->instructor->givePermissionTo('modules.edit');

        Livewire::test(ModuleForm::class, [
            'course' => $this->course,
            'module' => $module,
        ])
            ->assertStatus(200)
            ->assertSee('Edit Module')
            ->assertSee($module->title);
    }

    public function test_can_create_module(): void
    {
        $this->instructor->givePermissionTo('modules.create');

        Livewire::test(ModuleForm::class, ['course' => $this->course])
            ->set('title', 'HTML Basics')
            ->set('description', 'Learn HTML')
            ->call('save')
            ->assertRedirect(route('courses.show', $this->course));

        $this->assertDatabaseHas('modules', [
            'title' => 'HTML Basics',
            'description' => 'Learn HTML',
            'course_id' => $this->course->id,
            'order' => 1,
        ]);
    }

    public function test_module_order_auto_increments(): void
    {
        $this->instructor->givePermissionTo('modules.create');

        Module::factory()
            ->for($this->course)
            ->create(['order' => 1]);

        Livewire::test(ModuleForm::class, ['course' => $this->course])
            ->set('title', 'CSS Basics')
            ->call('save');

        $this->assertDatabaseHas('modules', [
            'title' => 'CSS Basics',
            'course_id' => $this->course->id,
            'order' => 2,
        ]);
    }

    public function test_can_update_module(): void
    {
        $module = Module::factory()
            ->for($this->course)
            ->create(['title' => 'Old Title']);

        $this->instructor->givePermissionTo('modules.edit');

        Livewire::test(ModuleForm::class, [
            'course' => $this->course,
            'module' => $module,
        ])
            ->set('title', 'New Title')
            ->set('description', 'Updated description')
            ->call('save')
            ->assertRedirect(route('courses.show', $this->course));

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'title' => 'New Title',
            'description' => 'Updated description',
        ]);
    }

    public function test_required_fields_validation(): void
    {
        $this->instructor->givePermissionTo('modules.create');

        Livewire::test(ModuleForm::class, ['course' => $this->course])
            ->set('title', '')
            ->call('save')
            ->assertHasErrors('title');
    }

    public function test_title_max_length_validation(): void
    {
        $this->instructor->givePermissionTo('modules.create');

        Livewire::test(ModuleForm::class, ['course' => $this->course])
            ->set('title', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors('title');
    }

    public function test_user_cannot_access_form_without_permission(): void
    {
        $this->expectException(AuthorizationException::class);

        Livewire::test(ModuleForm::class, ['course' => $this->course]);
    }

    public function test_user_cannot_create_module_in_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()
            ->for($otherSchool)
            ->create();

        $this->instructor->givePermissionTo('modules.create');

        $this->expectException(AuthorizationException::class);

        Livewire::test(ModuleForm::class, ['course' => $otherCourse]);
    }
}
