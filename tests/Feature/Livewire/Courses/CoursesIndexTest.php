<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\CoursesIndex;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CoursesIndexTest extends TestCase
{
    private School $school;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->instructor = User::factory()
            ->for($this->school)
            ->create();

        $this->actingAs($this->instructor);
    }

    public function test_courses_index_component_renders(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Livewire::test(CoursesIndex::class)
            ->assertStatus(200)
            ->assertSee('Courses');
    }

    public function test_displays_courses_for_school(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Python Basics']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'JavaScript Advanced']);

        Livewire::test(CoursesIndex::class)
            ->assertSee('Python Basics')
            ->assertSee('JavaScript Advanced');
    }

    public function test_does_not_display_courses_from_other_schools(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $otherSchool = School::factory()->create();
        Course::factory()
            ->for($otherSchool)
            ->create(['title' => 'Hidden Course']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'My Course']);

        Livewire::test(CoursesIndex::class)
            ->assertSee('My Course')
            ->assertDontSee('Hidden Course');
    }

    public function test_can_search_courses_by_title(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'PHP Fundamentals']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'JavaScript Basics']);

        Livewire::test(CoursesIndex::class)
            ->set('search', 'PHP')
            ->assertSee('PHP Fundamentals')
            ->assertDontSee('JavaScript Basics');
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'PHP Fundamentals']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'JavaScript Basics']);

        Livewire::test(CoursesIndex::class)
            ->set('search', 'php fundamentals')
            ->assertSee('PHP Fundamentals')
            ->assertDontSee('JavaScript Basics');
    }

    public function test_can_search_courses_by_description(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Course A', 'description' => 'Learn web development']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Course B', 'description' => 'Learn mobile development']);

        Livewire::test(CoursesIndex::class)
            ->set('search', 'web')
            ->assertSee('Course A')
            ->assertDontSee('Course B');
    }

    public function test_displays_empty_state_when_no_courses(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Livewire::test(CoursesIndex::class)
            ->assertSee('No courses yet');
    }

    public function test_displays_empty_state_when_search_has_no_results(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Python Basics']);

        Livewire::test(CoursesIndex::class)
            ->set('search', 'JavaScript')
            ->assertSee('No courses found');
    }

    public function test_displays_course_module_count(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Complete Course']);

        Livewire::test(CoursesIndex::class)
            ->assertSee('0 modules');
    }

    public function test_displays_published_status(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Published Course', 'is_published' => true]);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Draft Course', 'is_published' => false]);

        Livewire::test(CoursesIndex::class)
            ->assertSee('Published')
            ->assertSee('Draft');
    }

    public function test_displays_course_creator(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->for($this->instructor, 'creator')
            ->create(['title' => 'My Course']);

        Livewire::test(CoursesIndex::class)
            ->assertSee('My Course');
    }

    public function test_pagination_works(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->count(15)
            ->create();

        $component = Livewire::test(CoursesIndex::class);
        $courses = $component->viewData('courses');

        $this->assertEquals(10, $courses->count());
        $this->assertTrue($courses->hasPages());
    }

    public function test_search_resets_pagination(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->count(15)
            ->create(['title' => 'Course']);

        Livewire::test(CoursesIndex::class)
            ->set('search', 'Course')
            ->assertSet('paginators.page.before.page', 1);
    }

    public function test_can_delete_course(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'courses.delete']);

        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Course To Delete']);

        Livewire::test(CoursesIndex::class)
            ->call('destroy', $course->id)
            ->assertSet('successMessage', 'Course deleted successfully.');

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_cannot_delete_course_without_permission(): void
    {
        $this->instructor->givePermissionTo('courses.view');

        $course = Course::factory()
            ->for($this->school)
            ->create();

        // Livewire's ->call() captures abort_unless(..., 403) as a response status
        // rather than re-throwing to PHPUnit — assertStatus is the correct check here.
        Livewire::test(CoursesIndex::class)
            ->call('destroy', $course->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_cannot_delete_course_from_different_school(): void
    {
        $this->instructor->givePermissionTo(['courses.view', 'courses.delete']);

        $otherSchool = School::factory()->create();
        $course = Course::factory()
            ->for($otherSchool)
            ->create();

        Livewire::test(CoursesIndex::class)
            ->call('destroy', $course->id)
            ->assertSet('errorMessage', 'Course not found.');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_user_cannot_access_without_permission(): void
    {
        Livewire::test(CoursesIndex::class)
            ->assertStatus(403);
    }
}
