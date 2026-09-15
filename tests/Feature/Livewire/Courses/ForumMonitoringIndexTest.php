<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Livewire\Courses\ForumMonitoringIndex;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ForumMonitoringIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    private Session $session;

    private Forum $forum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();
        $this->session = Session::factory()->create([
            'course_id' => $this->course->id,
            'delivery_mode' => DeliveryMode::Online,
            'required_forum_posts' => 2,
            'order' => 1,
        ]);
        $this->forum = Forum::factory()->create(['course_id' => $this->course->id, 'session_id' => $this->session->id]);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
    }

    public function test_user_without_permission_cannot_view_forum_monitoring(): void
    {
        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_user_cannot_view_forum_monitoring_of_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo('forum.moderate');

        Livewire::test(ForumMonitoringIndex::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }

    public function test_shows_session_title_and_student_post_counts(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->student->id]);
        ForumComment::factory()->for($thread, 'thread')->create(['user_id' => $this->student->id]);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee($this->session->title)
            ->assertSee($this->student->name)
            ->assertSee('Met');
    }

    public function test_shows_remaining_posts_needed_when_requirement_not_met(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        ForumThread::factory()->for($this->forum)->create(['user_id' => $this->student->id]);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee($this->student->name)
            ->assertSee('1 post remaining');
    }

    public function test_can_select_a_specific_session_via_query_param(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        $otherSession = Session::factory()->create([
            'course_id' => $this->course->id,
            'delivery_mode' => DeliveryMode::Online,
            'order' => 2,
        ]);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course, 'sessionId' => $otherSession->id])
            ->call('loadData')
            ->assertSee($otherSession->title);
    }

    public function test_offline_sessions_cannot_be_selected(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        $offlineSession = Session::factory()->create([
            'course_id' => $this->course->id,
            'delivery_mode' => DeliveryMode::Offline,
            'order' => 2,
        ]);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course, 'sessionId' => $offlineSession->id])
            ->call('loadData')
            ->assertSee($this->session->title)
            ->assertDontSee($offlineSession->title);
    }

    public function test_back_to_forum_link_points_to_the_selected_session(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Back to Forum')
            ->assertSee(route('forum.index', [$this->course, 'session' => $this->session->id]), false);
    }

    public function test_view_student_posts_shows_their_threads_and_comments(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->student->id, 'title' => 'My Thread Title']);
        ForumComment::factory()->for($thread, 'thread')->create(['user_id' => $this->student->id, 'body' => 'My comment body']);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('My Thread Title')
            ->assertSee('My comment body');
    }

    public function test_teacher_can_delete_a_students_thread(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->student->id]);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('deleteStudentThread', $thread->id);

        $this->assertDatabaseMissing('forum_threads', ['id' => $thread->id]);
    }

    public function test_teacher_can_delete_a_students_comment(): void
    {
        $this->teacher->givePermissionTo('forum.moderate');

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->student->id]);
        $comment = ForumComment::factory()->for($thread, 'thread')->create(['user_id' => $this->student->id]);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('deleteStudentComment', $comment->id);

        $this->assertDatabaseMissing('forum_comments', ['id' => $comment->id]);
    }

    public function test_autofill_comments_creates_two_comments_per_student_in_local_env(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->teacher->givePermissionTo('forum.moderate');

        $otherStudent = User::factory()->forSchool($this->school)->create();
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $otherStudent->id]);

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('autofillComments');

        $this->assertSame(2, ForumComment::whereHas('thread.forum', fn ($q) => $q->where('session_id', $this->session->id))->where('user_id', $this->student->id)->count());
        $this->assertSame(2, ForumComment::whereHas('thread.forum', fn ($q) => $q->where('session_id', $this->session->id))->where('user_id', $otherStudent->id)->count());
    }

    public function test_autofill_comments_is_forbidden_outside_local_or_testing_env(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->teacher->givePermissionTo('forum.moderate');

        Livewire::test(ForumMonitoringIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('autofillComments')
            ->assertStatus(403);
    }
}
