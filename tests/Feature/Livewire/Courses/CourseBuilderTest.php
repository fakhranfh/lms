<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\CourseBuilder;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Tests\TestCase;

class CourseBuilderTest extends TestCase
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

    public function test_course_builder_component_renders(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->assertStatus(200)
            ->assertSee($this->course->title);
    }

    public function test_displays_modules_for_course(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $module1 = Module::factory()
            ->for($this->course)
            ->create(['title' => 'Module One']);
        $module2 = Module::factory()
            ->for($this->course)
            ->create(['title' => 'Module Two']);

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->assertSee('Module One')
            ->assertSee('Module Two');
    }

    public function test_displays_lessons_when_module_expanded(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $module = Module::factory()
            ->for($this->course)
            ->create();
        $lesson = Lesson::factory()
            ->for($module)
            ->create(['title' => 'Lesson One']);

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('toggleModule', $module->id)
            ->assertSee('Lesson One');
    }

    public function test_can_toggle_module_expansion(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $module = Module::factory()
            ->for($this->course)
            ->create();

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('toggleModule', $module->id)
            ->assertSet('expandedModules.'.$module->id, true)
            ->call('toggleModule', $module->id)
            ->assertSet('expandedModules.'.$module->id, false);
    }

    public function test_can_move_module_up(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'modules.edit']);

        $module1 = Module::factory()
            ->for($this->course)
            ->create(['order' => 1]);
        $module2 = Module::factory()
            ->for($this->course)
            ->create(['order' => 2]);

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('moveModuleUp', $module2->id);

        $this->assertEquals(2, $module1->fresh()->order);
        $this->assertEquals(1, $module2->fresh()->order);
    }

    public function test_can_move_module_down(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'modules.edit']);

        $module1 = Module::factory()
            ->for($this->course)
            ->create(['order' => 1]);
        $module2 = Module::factory()
            ->for($this->course)
            ->create(['order' => 2]);

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('moveModuleDown', $module1->id);

        $this->assertEquals(2, $module1->fresh()->order);
        $this->assertEquals(1, $module2->fresh()->order);
    }

    public function test_can_move_lesson_up(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'lessons.edit']);

        $module = Module::factory()
            ->for($this->course)
            ->create();
        $lesson1 = Lesson::factory()
            ->for($module)
            ->create(['order' => 1]);
        $lesson2 = Lesson::factory()
            ->for($module)
            ->create(['order' => 2]);

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('moveLessonUp', $lesson2->id);

        $this->assertEquals(2, $lesson1->fresh()->order);
        $this->assertEquals(1, $lesson2->fresh()->order);
    }

    public function test_can_move_lesson_down(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'lessons.edit']);

        $module = Module::factory()
            ->for($this->course)
            ->create();
        $lesson1 = Lesson::factory()
            ->for($module)
            ->create(['order' => 1]);
        $lesson2 = Lesson::factory()
            ->for($module)
            ->create(['order' => 2]);

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('moveLessonDown', $lesson1->id);

        $this->assertEquals(2, $lesson1->fresh()->order);
        $this->assertEquals(1, $lesson2->fresh()->order);
    }

    public function test_can_delete_module(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'modules.delete']);

        $module = Module::factory()
            ->for($this->course)
            ->create();

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('confirmDelete', 'modules', $module->id);

        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    public function test_can_delete_lesson(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'lessons.delete']);

        $module = Module::factory()
            ->for($this->course)
            ->create();
        $lesson = Lesson::factory()
            ->for($module)
            ->create();

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->call('confirmDelete', 'lessons', $lesson->id);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_user_cannot_access_builder_without_permission(): void
    {
        $this->expectException(AuthorizationException::class);

        Livewire::test(CourseBuilder::class, ['course' => $this->course]);
    }

    public function test_user_cannot_view_course_from_different_school(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()
            ->for($otherSchool)
            ->create();

        $this->instructor->givePermissionTo('courses.view');

        $this->expectException(AuthorizationException::class);

        Livewire::test(CourseBuilder::class, ['course' => $otherCourse]);
    }

    public function test_displays_empty_state_when_no_modules(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->assertSee('No modules yet');
    }

    public function test_displays_course_description(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $this->course->update(['description' => 'This is a course description']);

        Livewire::test(CourseBuilder::class, ['course' => $this->course])
            ->assertSee('This is a course description');
    }

    public function test_shows_publish_status(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $publishedCourse = Course::factory()
            ->for($this->school)
            ->create(['is_published' => true]);

        Livewire::test(CourseBuilder::class, ['course' => $publishedCourse])
            ->assertSee('Published');
    }

    public function test_refresh_on_module_created_event(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $component = Livewire::test(CourseBuilder::class, ['course' => $this->course]);

        $component->dispatch('module-created');

        $this->assertNull($component->get('errorMessage'));
    }
}
