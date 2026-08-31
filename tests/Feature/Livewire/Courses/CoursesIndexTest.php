<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Enums\RoleName;
use App\Livewire\Courses\CoursesIndex;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\MediaLibraryItem;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\SessionMaterialCompletion;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CoursesIndexTest extends TestCase
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

    public function test_courses_index_component_renders(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Livewire::test(CoursesIndex::class)
            ->assertStatus(200)
            ->call('loadCourses')
            ->assertSee('Courses');
    }

    public function test_displays_courses_for_school(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Python Basics']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'JavaScript Advanced']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('Python Basics')
            ->assertSee('JavaScript Advanced');
    }

    public function test_does_not_display_courses_from_other_schools(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        $otherSchool = School::factory()->create();
        Course::factory()
            ->for($otherSchool)
            ->create(['title' => 'Hidden Course']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'My Course']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('My Course')
            ->assertDontSee('Hidden Course');
    }

    public function test_can_search_courses_by_title(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'PHP Fundamentals']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'JavaScript Basics']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->set('search', 'PHP')
            ->assertSee('PHP Fundamentals')
            ->assertDontSee('JavaScript Basics');
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'PHP Fundamentals']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'JavaScript Basics']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->set('search', 'php fundamentals')
            ->assertSee('PHP Fundamentals')
            ->assertDontSee('JavaScript Basics');
    }

    public function test_can_search_courses_by_description(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Course A', 'description' => 'Learn web development']);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Course B', 'description' => 'Learn mobile development']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->set('search', 'web')
            ->assertSee('Course A')
            ->assertDontSee('Course B');
    }

    public function test_displays_empty_state_when_no_courses(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('No courses yet');
    }

    public function test_displays_empty_state_when_search_has_no_results(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Python Basics']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->set('search', 'JavaScript')
            ->assertSee('No courses found');
    }

    public function test_displays_course_session_count(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Complete Course']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('0 sessions');
    }

    public function test_displays_published_status(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Published Course', 'is_published' => true]);
        Course::factory()
            ->for($this->school)
            ->create(['title' => 'Draft Course', 'is_published' => false]);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('Published')
            ->assertSee('Draft');
    }

    public function test_displays_course_creator(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->for($this->teacher, 'creator')
            ->create(['title' => 'My Course']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('My Course');
    }

    public function test_pagination_works(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->count(15)
            ->create();

        $component = Livewire::test(CoursesIndex::class)
            ->call('loadCourses');
        $courses = $component->viewData('courses');

        $this->assertEquals(10, $courses->count());
        $this->assertTrue($courses->hasPages());
    }

    public function test_search_resets_pagination(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        Course::factory()
            ->for($this->school)
            ->count(15)
            ->create(['title' => 'Course']);

        $component = Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->set('search', 'Course');

        $courses = $component->viewData('courses');
        $this->assertEquals(1, $courses->currentPage());
    }

    public function test_can_delete_course(): void
    {
        $this->teacher->givePermissionTo(['courses.view', 'courses.delete']);

        $course = Course::factory()
            ->for($this->school)
            ->create(['title' => 'Course To Delete']);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->call('destroy', $course->id)
            ->assertSet('successMessage', 'Course deleted successfully.');

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_cannot_delete_course_without_permission(): void
    {
        $this->teacher->givePermissionTo('courses.view');

        $course = Course::factory()
            ->for($this->school)
            ->create();

        // Livewire's ->call() captures abort_unless(..., 403) as a response status
        // rather than re-throwing to PHPUnit — assertStatus is the correct check here.
        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->call('destroy', $course->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_cannot_delete_course_from_different_school(): void
    {
        $this->teacher->givePermissionTo(['courses.view', 'courses.delete']);

        $otherSchool = School::factory()->create();
        $course = Course::factory()
            ->for($otherSchool)
            ->create();

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->call('destroy', $course->id)
            ->assertSet('errorMessage', 'Course not found.');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_user_cannot_access_without_permission(): void
    {
        Livewire::test(CoursesIndex::class)
            ->assertStatus(403);
    }

    public function test_student_does_not_see_draft_courses_or_published_status(): void
    {
        $student = User::factory()->forSchool($this->school)->create();
        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $student->assignRole($studentRole);
        $student->givePermissionTo('courses.view');

        $enrolledCourse = Course::factory()->for($this->school)->create(['title' => 'Intro to Web Development', 'is_published' => true]);
        Course::factory()->for($this->school)->create(['title' => 'Unfinished Course', 'is_published' => false]);

        CoursePerson::factory()->create([
            'course_id' => $enrolledCourse->id,
            'user_id' => $student->id,
            'role_in_course' => RoleInCourse::Student,
            'status' => CourseMembershipStatus::Active,
        ]);

        $this->actingAs($student);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('Intro to Web Development')
            ->assertDontSee('Unfinished Course')
            ->assertDontSee('Published')
            ->assertDontSee('Draft');
    }

    public function test_student_does_not_see_courses_they_are_not_enrolled_in(): void
    {
        $student = User::factory()->forSchool($this->school)->create();
        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $student->assignRole($studentRole);
        $student->givePermissionTo('courses.view');

        Course::factory()->for($this->school)->create(['title' => 'Not Enrolled Course', 'is_published' => true]);

        $this->actingAs($student);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertDontSee('Not Enrolled Course');
    }

    public function test_student_sees_course_progress_percentage(): void
    {
        $student = User::factory()->forSchool($this->school)->create();
        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $student->assignRole($studentRole);
        $student->givePermissionTo('courses.view');

        $course = Course::factory()->for($this->school)->create(['title' => 'Progress Course', 'is_published' => true]);
        $session = Session::factory()->for($course)->create();
        $materialOne = MediaLibraryItem::factory()->for($this->school)->create();
        $materialTwo = MediaLibraryItem::factory()->for($this->school)->create();
        $session->materials()->attach([$materialOne->id => ['order' => 1], $materialTwo->id => ['order' => 2]]);

        CoursePerson::factory()->create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role_in_course' => RoleInCourse::Student,
            'status' => CourseMembershipStatus::Active,
        ]);

        SessionMaterialCompletion::create([
            'session_id' => $session->id,
            'media_library_item_id' => $materialOne->id,
            'user_id' => $student->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($student);

        Livewire::test(CoursesIndex::class)
            ->call('loadCourses')
            ->assertSee('Progress Course')
            ->assertSee('50%');
    }
}
