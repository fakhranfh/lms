<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\CourseForm;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CourseFormTest extends TestCase
{
    private School $school;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()
            ->forSchool($this->school)
            ->create();

        $this->actingAs($this->teacher);
    }

    public function test_create_course_component_renders(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->assertStatus(200)
            ->assertSee('Create New Course');
    }

    public function test_edit_course_component_renders(): void
    {
        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Test Course Title']);

        $this->teacher->givePermissionTo('courses.edit');

        Livewire::test(CourseForm::class, ['course' => $course])
            ->assertStatus(200)
            ->assertSee('Edit Course')
            ->assertSee('Course Title');
    }

    public function test_can_create_course(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', 'PHP Fundamentals')
            ->set('description', 'Learn PHP basics')
            ->call('save')
            ->assertRedirect(route('courses.show', ['course' => Course::latest()->first()]));

        $this->assertDatabaseHas('courses', [
            'title' => 'PHP Fundamentals',
            'description' => 'Learn PHP basics',
            'slug' => 'php-fundamentals',
            'school_id' => $this->school->id,
            'created_by' => $this->teacher->id,
        ]);
    }

    public function test_creating_course_adds_creator_as_teacher(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', 'PHP Fundamentals')
            ->set('description', 'Learn PHP basics')
            ->call('save');

        $course = Course::where('title', 'PHP Fundamentals')->firstOrFail();

        $this->assertDatabaseHas('course_people', [
            'course_id' => $course->id,
            'user_id' => $this->teacher->id,
            'role_in_course' => 'teacher',
            'status' => 'active',
        ]);
    }

    public function test_creating_course_restores_soft_deleted_course_with_same_slug(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        $trashed = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Old Title', 'slug' => 'php-fundamentals']);
        $trashedId = $trashed->id;
        $trashed->delete();

        Livewire::test(CourseForm::class)
            ->set('title', 'PHP Fundamentals')
            ->set('description', 'Learn PHP basics')
            ->call('save');

        $this->assertSame(1, Course::withTrashed()->where('slug', 'php-fundamentals')->count());
        $this->assertDatabaseHas('courses', [
            'id' => $trashedId,
            'title' => 'PHP Fundamentals',
            'description' => 'Learn PHP basics',
            'slug' => 'php-fundamentals',
            'created_by' => $this->teacher->id,
            'deleted_at' => null,
        ]);
    }

    public function test_auto_generates_unique_slug_when_title_collides(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Duplicate Title', 'slug' => 'duplicate-title']);

        Livewire::test(CourseForm::class)
            ->set('title', 'Duplicate Title')
            ->call('save');

        $this->assertDatabaseHas('courses', [
            'title' => 'Duplicate Title',
            'slug' => 'duplicate-title-2',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_double_submit_does_not_create_duplicate_course(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        // Simulates a double-click: two submissions of the same new-course
        // form before either has a persisted slug to collide against.
        Livewire::test(CourseForm::class)
            ->set('title', 'Data Structures')
            ->call('save');

        Livewire::test(CourseForm::class)
            ->set('title', 'Data Structures')
            ->call('save');

        $this->assertSame(2, Course::where('title', 'Data Structures')->count());
        $this->assertDatabaseHas('courses', ['title' => 'Data Structures', 'slug' => 'data-structures']);
        $this->assertDatabaseHas('courses', ['title' => 'Data Structures', 'slug' => 'data-structures-2']);
    }

    public function test_can_update_course(): void
    {
        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Old Title']);

        $this->teacher->givePermissionTo('courses.edit');

        Livewire::test(CourseForm::class, ['course' => $course])
            ->set('title', 'New Title')
            ->set('description', 'Updated description')
            ->call('save')
            ->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'New Title',
            'description' => 'Updated description',
        ]);
    }

    public function test_can_publish_course(): void
    {
        $course = Course::factory()
            ->for($this->school)
            ->create(['is_published' => false]);

        $this->teacher->givePermissionTo('courses.edit');

        Livewire::test(CourseForm::class, ['course' => $course])
            ->set('isPublished', true)
            ->call('save');

        $this->assertTrue($course->fresh()->is_published);
    }

    public function test_required_fields_validation(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors('title');
    }

    public function test_title_max_length_validation(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors('title');
    }

    public function test_auto_fill_button_is_visible_in_local_environment(): void
    {
        $this->teacher->givePermissionTo('courses.create');
        $this->app->detectEnvironment(fn () => 'local');

        Livewire::test(CourseForm::class)
            ->assertSee('Dev: Auto-fill');
    }

    public function test_auto_fill_button_is_hidden_outside_local_environment(): void
    {
        $this->teacher->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->assertDontSee('Dev: Auto-fill');
    }

    public function test_user_cannot_access_form_without_permission(): void
    {
        Livewire::test(CourseForm::class)
            ->assertStatus(403);
    }

    public function test_user_cannot_edit_course_from_different_school(): void
    {
        $otherSchool = School::factory()->create();
        $course = Course::factory()
            ->for($otherSchool)
            ->create();

        $this->teacher->givePermissionTo('courses.edit');

        Livewire::test(CourseForm::class, ['course' => $course])
            ->assertStatus(403);
    }
}
