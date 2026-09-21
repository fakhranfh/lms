<?php

namespace Tests\Feature\Livewire;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Enums\RoleName;
use App\Livewire\Dashboard;
use App\Livewire\Dashboard\TodoList;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\MediaLibraryItem;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\SessionMaterialCompletion;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private School $school;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->student = User::factory()->forSchool($this->school)->create();

        $studentRole = Role::firstOrCreate([
            'name' => RoleName::Student->value,
            'guard_name' => 'web',
            'school_id' => $this->school->id,
        ]);
        $this->student->assignRole($studentRole);
        $this->student->givePermissionTo('courses.view');

        $this->course = Course::factory()->for($this->school)->create(['title' => 'Dashboard Course']);

        CoursePerson::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role_in_course' => RoleInCourse::Student,
            'status' => CourseMembershipStatus::Active,
        ]);
    }

    public function test_student_sees_progress_todo_and_forum_sections(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $materialOne = MediaLibraryItem::factory()->for($this->school)->create(['title' => 'Viewed Material']);
        $materialTwo = MediaLibraryItem::factory()->for($this->school)->create(['title' => 'Pending Material']);
        $session->materials()->attach([$materialOne->id => ['order' => 1], $materialTwo->id => ['order' => 2]]);

        SessionMaterialCompletion::create([
            'session_id' => $session->id,
            'media_library_item_id' => $materialOne->id,
            'user_id' => $this->student->id,
            'completed_at' => now(),
        ]);

        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
            'session_id' => null,
            'type' => AssessmentType::TheoryQuiz,
            'title' => 'Pending Quiz',
            'assigned_to' => AssessmentAssignedTo::Individual,
            'status' => AssessmentStatus::Published,
        ]);

        $forum = Forum::factory()->create(['course_id' => $this->course->id]);
        ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'user_id' => $this->student->id,
            'title' => 'Welcome thread',
        ]);

        $this->actingAs($this->student);

        Livewire::withoutLazyLoading()
            ->test(Dashboard::class)
            ->call('loadStudentData')
            ->assertSee('My Progress')
            ->assertSee('Dashboard Course')
            ->assertSee('To-Do')
            ->assertSee('Latest Forum Posts')
            ->assertSee('Welcome thread');

        Livewire::withoutLazyLoading()
            ->test(TodoList::class)
            ->assertSee('Pending Material')
            ->assertSee('Pending Quiz')
            ->assertDontSee('Viewed Material');
    }

    public function test_todo_list_is_lazy_loaded(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['title' => 'Lazy Material']);
        $session->materials()->attach([$material->id => ['order' => 1]]);

        $this->actingAs($this->student);

        Livewire::test(Dashboard::class)
            ->call('loadStudentData')
            ->assertDontSee('Lazy Material');

        Livewire::withoutLazyLoading()
            ->test(TodoList::class)
            ->assertSee('Lazy Material');
    }

    public function test_todo_list_can_be_filtered_by_type(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['title' => 'Filter Material']);
        $session->materials()->attach([$material->id => ['order' => 1]]);

        Assessment::factory()->create([
            'course_id' => $this->course->id,
            'session_id' => null,
            'type' => AssessmentType::TheoryQuiz,
            'title' => 'Filter Quiz',
            'assigned_to' => AssessmentAssignedTo::Individual,
            'status' => AssessmentStatus::Published,
        ]);

        $this->actingAs($this->student);

        Livewire::withoutLazyLoading()
            ->test(TodoList::class)
            ->set('todoTypeFilter', 'material')
            ->assertSee('Filter Material')
            ->assertDontSee('Filter Quiz')
            ->set('todoTypeFilter', 'assessment')
            ->assertDontSee('Filter Material')
            ->assertSee('Filter Quiz');
    }

    public function test_completed_assessment_does_not_appear_in_todo_list(): void
    {
        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
            'session_id' => null,
            'type' => AssessmentType::TheoryQuiz,
            'title' => 'Submitted Quiz',
            'assigned_to' => AssessmentAssignedTo::Individual,
            'status' => AssessmentStatus::Published,
        ]);

        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->student->id,
            'attempt_number' => 1,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->student);

        Livewire::withoutLazyLoading()
            ->test(TodoList::class)
            ->assertDontSee('Submitted Quiz');
    }

    public function test_forum_posts_only_show_threads_from_enrolled_courses(): void
    {
        $otherCourse = Course::factory()->for($this->school)->create(['title' => 'Other Course']);
        $otherForum = Forum::factory()->create(['course_id' => $otherCourse->id]);
        ForumThread::factory()->create([
            'forum_id' => $otherForum->id,
            'title' => 'Not my course thread',
        ]);

        $this->actingAs($this->student);

        Livewire::test(Dashboard::class)
            ->call('loadStudentData')
            ->assertDontSee('Not my course thread');
    }

    public function test_non_student_does_not_see_student_dashboard_sections(): void
    {
        $teacher = User::factory()->forSchool($this->school)->create();
        $teacherRole = Role::firstOrCreate([
            'name' => RoleName::Teacher->value,
            'guard_name' => 'web',
            'school_id' => $this->school->id,
        ]);
        $teacher->assignRole($teacherRole);

        $this->actingAs($teacher);

        Livewire::test(Dashboard::class)
            ->call('loadStudentData')
            ->assertDontSee('My Progress')
            ->assertDontSee('To-Do')
            ->assertDontSee('Latest Forum Posts');
    }
}
