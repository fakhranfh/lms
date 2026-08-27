<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\CourseComingSoon;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CourseComingSoonTest extends TestCase
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
        $this->teacher->givePermissionTo('courses.view');
    }

    public function test_unknown_tab_returns_404(): void
    {
        Livewire::test(CourseComingSoon::class, ['course' => $this->course, 'tab' => 'not-a-real-tab'])
            ->assertStatus(404);
    }

    public function test_forum_tab_is_no_longer_coming_soon(): void
    {
        Livewire::test(CourseComingSoon::class, ['course' => $this->course, 'tab' => 'forum'])
            ->assertStatus(404);
    }

    public function test_assessment_tab_is_no_longer_coming_soon(): void
    {
        Livewire::test(CourseComingSoon::class, ['course' => $this->course, 'tab' => 'assessment'])
            ->assertStatus(404);
    }

    public function test_gradebook_tab_is_no_longer_coming_soon(): void
    {
        Livewire::test(CourseComingSoon::class, ['course' => $this->course, 'tab' => 'gradebook'])
            ->assertStatus(404);
    }

    public function test_people_tab_is_no_longer_coming_soon(): void
    {
        Livewire::test(CourseComingSoon::class, ['course' => $this->course, 'tab' => 'people'])
            ->assertStatus(404);
    }

    public function test_user_cannot_view_coming_soon_for_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        Livewire::test(CourseComingSoon::class, ['course' => $otherCourse, 'tab' => 'not-a-real-tab'])
            ->assertStatus(403);
    }
}
