<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\ForumThreadShow;
use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\ForumThreadRead;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ForumThreadShowTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    private Session $session;

    private Forum $forum;

    private ForumThread $thread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();
        $this->session = Session::factory()->for($this->course)->create();
        $this->forum = Forum::factory()->for($this->course)->create(['session_id' => $this->session->id]);
        $this->thread = ForumThread::factory()->for($this->forum)->create(['user_id' => $this->teacher->id]);

        $this->actingAs($this->teacher);
    }

    public function test_user_cannot_access_thread_without_permission(): void
    {
        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->assertStatus(403);
    }

    public function test_cross_course_thread_access_is_not_found(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $otherCourse = Course::factory()->for($this->school)->create();

        Livewire::test(ForumThreadShow::class, ['course' => $otherCourse, 'thread' => $this->thread])
            ->assertStatus(404);
    }

    public function test_renders_thread_with_empty_comments_state(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->assertSee($this->thread->title)
            ->assertSee('No comments yet.');
    }

    public function test_add_comment_increments_comments_count(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('newCommentBody', 'A helpful reply')
            ->call('addComment')
            ->assertSee('A helpful reply');

        $this->assertSame(1, $this->thread->fresh()->comments_count);
    }

    public function test_owner_can_delete_own_comment_and_decrements_count(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['user_id' => $this->teacher->id]);
        ForumThread::whereKey($this->thread->id)->increment('comments_count');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('forum_comments', ['id' => $comment->id]);
        $this->assertSame(0, $this->thread->fresh()->comments_count);
    }

    public function test_deleting_comment_with_replies_decrements_count_by_all_removed_posts(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['user_id' => $this->teacher->id]);
        ForumComment::factory()->for($this->thread, 'thread')->create(['parent_id' => $comment->id, 'user_id' => $this->student->id]);
        ForumComment::factory()->for($this->thread, 'thread')->create(['parent_id' => $comment->id, 'user_id' => $this->student->id]);
        ForumThread::whereKey($this->thread->id)->increment('comments_count', 3);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('forum_comments', ['id' => $comment->id]);
        $this->assertSame(0, $this->thread->fresh()->comments_count);
    }

    public function test_teacher_can_moderate_delete_students_comment(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.moderate']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['user_id' => $this->student->id]);
        ForumThread::whereKey($this->thread->id)->increment('comments_count');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('forum_comments', ['id' => $comment->id]);
    }

    public function test_student_cannot_delete_others_comment_without_moderate(): void
    {
        $this->teacher->givePermissionTo(['forum.view']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['user_id' => $this->student->id]);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('deleteComment', $comment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_comments', ['id' => $comment->id]);
    }

    public function test_owner_can_delete_thread_and_is_redirected(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('deleteThread')
            ->assertRedirect(route('forum.index', [$this->course, 'session' => $this->session->id]));

        $this->assertDatabaseMissing('forum_threads', ['id' => $this->thread->id]);
    }

    public function test_non_owner_non_moderator_cannot_delete_thread(): void
    {
        $this->actingAs($this->student);
        $this->student->givePermissionTo('forum.view');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('deleteThread')
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_threads', ['id' => $this->thread->id]);
    }

    public function test_opening_thread_marks_it_as_read_for_current_user(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread]);

        $this->assertDatabaseHas('forum_thread_reads', [
            'thread_id' => $this->thread->id,
            'user_id' => $this->teacher->id,
        ]);
    }

    public function test_reopening_thread_updates_existing_read_record_instead_of_duplicating(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        ForumThreadRead::factory()->create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->teacher->id,
            'read_at' => now()->subDay(),
        ]);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread]);

        $this->assertDatabaseCount('forum_thread_reads', 1);
    }

    public function test_owner_can_update_thread(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('startEditThread')
            ->set('editThreadTitle', 'Updated title')
            ->set('editThreadDescription', 'Updated body')
            ->call('updateThread')
            ->assertSee('Updated title');

        $this->assertSame('Updated title', $this->thread->fresh()->title);
    }

    public function test_owner_can_update_own_comment(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['user_id' => $this->teacher->id, 'body' => 'Original']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('startEditComment', $comment->id)
            ->set('editCommentBody', 'Edited body')
            ->call('updateComment')
            ->assertSee('Edited body');

        $this->assertSame('Edited body', $comment->fresh()->body);
    }

    public function test_reply_to_top_level_comment_is_created_and_increments_thread_count(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create();
        ForumThread::whereKey($this->thread->id)->increment('comments_count');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('startReply', $comment->id)
            ->set('newReplyBody', 'A reply')
            ->call('addReply')
            ->assertSee('A reply');

        $this->assertDatabaseHas('forum_comments', ['parent_id' => $comment->id, 'body' => 'A reply']);
        $this->assertSame(2, $this->thread->fresh()->comments_count);
    }

    public function test_cannot_reply_to_a_reply(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create();
        $reply = ForumComment::factory()->for($this->thread, 'thread')->create(['parent_id' => $comment->id]);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('startReply', $reply->id)
            ->set('newReplyBody', 'Nested reply')
            ->call('addReply')
            ->assertStatus(404);
    }
}
