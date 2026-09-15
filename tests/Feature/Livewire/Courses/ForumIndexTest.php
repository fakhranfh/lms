<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Livewire\Courses\ForumIndex;
use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumThreadRead;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ForumIndexTest extends TestCase
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
        $this->session = Session::factory()->for($this->course)->create([
            'date_start' => now()->subDay(),
            'date_end' => now()->addDay(),
            'delivery_mode' => DeliveryMode::Online,
        ]);
        $this->forum = Forum::factory()->for($this->course)->create(['session_id' => $this->session->id]);

        $this->actingAs($this->teacher);
    }

    public function test_user_cannot_access_index_without_permission(): void
    {
        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_user_cannot_view_forum_of_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo('forum.view');

        Livewire::test(ForumIndex::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }

    public function test_shows_empty_state_when_course_has_no_sessions(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $emptyCourse = Course::factory()->for($this->school)->create();

        Livewire::test(ForumIndex::class, ['course' => $emptyCourse])
            ->call('loadForum')
            ->assertSee('No sessions yet.');
    }

    public function test_shows_empty_state_when_no_threads(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->assertSee('No threads yet.');
    }

    public function test_defaults_to_first_session_forum_and_lists_its_threads(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $thread = ForumThread::factory()->for($this->forum)->create(['title' => 'Welcome thread']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->assertSee('Welcome thread');

        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id]);
    }

    public function test_session_forum_shows_only_that_forums_threads(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        ForumThread::factory()->for($this->forum)->create(['title' => 'Session-one thread']);

        $otherSession = Session::factory()->for($this->course)->create(['delivery_mode' => DeliveryMode::Online]);
        $otherForum = Forum::factory()->for($this->course)->create(['session_id' => $otherSession->id]);
        ForumThread::factory()->for($otherForum)->create(['title' => 'Session-two thread']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('selectSession', $this->session->id)
            ->assertSee('Session-one thread')
            ->assertDontSee('Session-two thread')
            ->call('selectSession', $otherSession->id)
            ->assertSee('Session-two thread')
            ->assertDontSee('Session-one thread');
    }

    public function test_teacher_and_student_can_create_thread(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->set('newThreadTitle', 'A new topic')
            ->set('newThreadDescription', 'Some details')
            ->call('createThread')
            ->assertSee('A new topic');

        $this->assertDatabaseHas('forum_threads', ['title' => 'A new topic', 'user_id' => $this->teacher->id]);

        $this->actingAs($this->student);
        $this->student->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->set('newThreadTitle', 'Student topic')
            ->call('createThread');

        $this->assertDatabaseHas('forum_threads', ['title' => 'Student topic', 'user_id' => $this->student->id]);
    }

    public function test_cannot_create_thread_outside_session_window(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $pastSession = Session::factory()->for($this->course)->create([
            'date_start' => now()->subWeeks(2),
            'date_end' => now()->subWeek(),
            'delivery_mode' => DeliveryMode::Online,
        ]);
        $pastForum = Forum::factory()->for($this->course)->create(['session_id' => $pastSession->id]);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('selectSession', $pastSession->id)
            ->set('newThreadTitle', 'Too late')
            ->call('createThread')
            ->assertStatus(403);

        $this->assertDatabaseMissing('forum_threads', ['title' => 'Too late', 'forum_id' => $pastForum->id]);
    }

    public function test_generate_threads_bulk_creates_fake_threads_in_local_env(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->set('generateThreadCount', 3)
            ->call('generateThreads');

        $this->assertDatabaseCount('forum_threads', 3);
        $this->assertSame(3, ForumThread::where('forum_id', $this->forum->id)->where('user_id', $this->teacher->id)->count());
    }

    public function test_generate_threads_caps_count_at_fifty(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->set('generateThreadCount', 999)
            ->call('generateThreads');

        $this->assertDatabaseCount('forum_threads', 50);
    }

    public function test_generate_threads_is_forbidden_outside_local_or_testing_env(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('generateThreads')
            ->assertStatus(403);
    }

    public function test_generate_threads_titles_are_not_lorem_ipsum(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->set('generateThreadCount', 3)
            ->call('generateThreads');

        $titles = ForumThread::where('forum_id', $this->forum->id)->pluck('title');

        foreach ($titles as $title) {
            $this->assertStringNotContainsStringIgnoringCase('lorem', $title);
            $this->assertStringNotContainsStringIgnoringCase('ipsum', $title);
        }
    }

    public function test_owner_can_delete_own_thread(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->teacher->id]);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('confirmDeleteThread', $thread->id);

        $this->assertDatabaseMissing('forum_threads', ['id' => $thread->id]);
    }

    public function test_teacher_can_moderate_delete_students_thread(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.moderate']);

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->student->id]);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('confirmDeleteThread', $thread->id);

        $this->assertDatabaseMissing('forum_threads', ['id' => $thread->id]);
    }

    public function test_teacher_can_bulk_delete_selected_threads(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.moderate']);

        $threadOne = ForumThread::factory()->for($this->forum)->create();
        $threadTwo = ForumThread::factory()->for($this->forum)->create();
        $keepThread = ForumThread::factory()->for($this->forum)->create();

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('bulkDeleteThreads', [$threadOne->id, $threadTwo->id]);

        $this->assertDatabaseMissing('forum_threads', ['id' => $threadOne->id]);
        $this->assertDatabaseMissing('forum_threads', ['id' => $threadTwo->id]);
        $this->assertDatabaseHas('forum_threads', ['id' => $keepThread->id]);
    }

    public function test_bulk_delete_threads_is_forbidden_without_moderate_permission(): void
    {
        $this->teacher->givePermissionTo(['forum.view']);

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->teacher->id]);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('bulkDeleteThreads', [$thread->id])
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id]);
    }

    public function test_bulk_delete_ignores_thread_ids_from_a_different_forum(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.moderate']);

        $otherSession = Session::factory()->for($this->course)->create(['delivery_mode' => DeliveryMode::Online]);
        $otherForum = Forum::factory()->for($this->course)->create(['session_id' => $otherSession->id]);
        $otherThread = ForumThread::factory()->for($otherForum)->create();

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('selectSession', $this->session->id)
            ->call('bulkDeleteThreads', [$otherThread->id]);

        $this->assertDatabaseHas('forum_threads', ['id' => $otherThread->id]);
    }

    public function test_student_cannot_delete_others_thread_without_moderate(): void
    {
        $this->teacher->givePermissionTo(['forum.view']);

        $thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->student->id]);

        Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->call('confirmDeleteThread', $thread->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id]);
    }

    public function test_pagination_limits_threads_per_page(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        ForumThread::factory()->for($this->forum)->count(3)->create();

        $component = Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum')
            ->set('perPage', 2);

        $threadRows = $component->viewData('threadRows');
        $pagination = $component->viewData('pagination');

        $this->assertCount(2, $threadRows);
        $this->assertSame(3, $pagination['total']);
        $this->assertSame(2, $pagination['lastPage']);
    }

    public function test_unread_badge_reflects_threads_not_yet_read_by_user(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $readThread = ForumThread::factory()->for($this->forum)->create();
        ForumThread::factory()->for($this->forum)->create();

        ForumThreadRead::factory()->create([
            'thread_id' => $readThread->id,
            'user_id' => $this->teacher->id,
            'read_at' => now(),
        ]);

        $component = Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum');

        $tabs = $component->viewData('sessionTabs');

        $this->assertSame(1, $tabs['visible'][0]['unreadCount']);
    }

    public function test_read_thread_title_is_not_bold(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $readThread = ForumThread::factory()->for($this->forum)->create(['title' => 'Already read thread']);
        ForumThreadRead::factory()->create([
            'thread_id' => $readThread->id,
            'user_id' => $this->teacher->id,
            'read_at' => now(),
        ]);

        $component = Livewire::test(ForumIndex::class, ['course' => $this->course])
            ->call('loadForum');

        $row = collect($component->viewData('threadRows'))->firstWhere('id', $readThread->id);

        $this->assertFalse($row['isUnread']);
    }
}
