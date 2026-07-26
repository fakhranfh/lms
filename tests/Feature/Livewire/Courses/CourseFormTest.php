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

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->instructor = User::factory()
            ->forSchool($this->school)
            ->create();

        $this->actingAs($this->instructor);
    }

    public function test_create_course_component_renders(): void
    {
        $this->instructor->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->assertStatus(200)
            ->assertSee('Create New Course');
    }

    public function test_edit_course_component_renders(): void
    {
        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Test Course Title']);

        $this->instructor->givePermissionTo('courses.edit');

        Livewire::test(CourseForm::class, ['course' => $course])
            ->assertStatus(200)
            ->assertSee('Edit Course')
            ->assertSee('Course Title');
    }

    public function test_can_create_course(): void
    {
        $this->instructor->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', 'PHP Fundamentals')
            ->set('description', 'Learn PHP basics')
            ->set('slug', 'php-fundamentals')
            ->call('save')
            ->assertRedirect(route('courses.show', ['course' => Course::latest()->first()]));

        $this->assertDatabaseHas('courses', [
            'title' => 'PHP Fundamentals',
            'description' => 'Learn PHP basics',
            'slug' => 'php-fundamentals',
            'school_id' => $this->school->id,
            'created_by' => $this->instructor->id,
        ]);
    }

    public function test_auto_generates_slug_from_title(): void
    {
        $this->instructor->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', 'Advanced JavaScript')
            ->assertSet('slug', 'advanced-javascript');
    }

    public function test_slug_must_be_unique_per_school(): void
    {
        $this->instructor->givePermissionTo('courses.create');

        Course::factory()
            ->for($this->school)
            ->create(['slug' => 'duplicate-slug']);

        Livewire::test(CourseForm::class)
            ->set('title', 'Another Course')
            ->set('slug', 'duplicate-slug')
            ->call('save')
            ->assertHasErrors('slug');
    }

    public function test_can_update_course(): void
    {
        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Old Title']);

        $this->instructor->givePermissionTo('courses.edit');

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

        $this->instructor->givePermissionTo('courses.edit');

        Livewire::test(CourseForm::class, ['course' => $course])
            ->set('isPublished', true)
            ->call('save');

        $this->assertTrue($course->fresh()->is_published);
    }

    public function test_required_fields_validation(): void
    {
        $this->instructor->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors('title');
    }

    public function test_title_max_length_validation(): void
    {
        $this->instructor->givePermissionTo('courses.create');

        Livewire::test(CourseForm::class)
            ->set('title', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors('title');
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

        $this->instructor->givePermissionTo('courses.edit');

        Livewire::test(CourseForm::class, ['course' => $course])
            ->assertStatus(403);
    }
}
