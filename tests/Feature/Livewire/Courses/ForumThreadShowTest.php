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
use App\Services\R2StorageService;
use Illuminate\Http\UploadedFile;
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
        $this->session = Session::factory()->for($this->course)->create([
            'date_start' => now()->subDay(),
            'date_end' => now()->addDay(),
        ]);
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

    public function test_cannot_add_comment_or_reply_outside_session_window(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $this->session->update([
            'date_start' => now()->subWeeks(2),
            'date_end' => now()->subWeek(),
        ]);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create();

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('newCommentBody', 'Too late')
            ->call('addComment')
            ->assertStatus(403);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('newReplyBody', 'Too late reply')
            ->call('addReply', $comment->id)
            ->assertStatus(403);
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

    public function test_deleting_comment_removes_its_r2_attachments(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create([
            'user_id' => $this->teacher->id,
            'body' => '<p>See <img src="https://r2.example.com/forum-attachments/photo.jpg"> and <a href="https://r2.example.com/forum-attachments/doc.pdf">doc.pdf</a></p>',
        ]);
        ForumThread::whereKey($this->thread->id)->increment('comments_count');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('isManagedUrl')->andReturn(true);
            $mock->shouldReceive('delete')->once()->with('https://r2.example.com/forum-attachments/photo.jpg')->andReturn(true);
            $mock->shouldReceive('delete')->once()->with('https://r2.example.com/forum-attachments/doc.pdf')->andReturn(true);
        });

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('forum_comments', ['id' => $comment->id]);
    }

    public function test_editing_comment_removes_r2_attachments_no_longer_referenced(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create([
            'user_id' => $this->teacher->id,
            'body' => '<p>See <img src="https://r2.example.com/forum-attachments/old.jpg"></p>',
        ]);

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('isManagedUrl')->andReturn(true);
            $mock->shouldReceive('delete')->once()->with('https://r2.example.com/forum-attachments/old.jpg')->andReturn(true);
        });

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('editCommentBody', 'No more image')
            ->call('updateComment', $comment->id);

        $this->assertSame('No more image', $comment->fresh()->body);
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

    public function test_teacher_cannot_edit_students_comment(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.moderate']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['user_id' => $this->student->id, 'body' => 'Original']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('editCommentBody', 'Edited by teacher')
            ->call('updateComment', $comment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_comments', ['id' => $comment->id, 'body' => 'Original']);
    }

    public function test_student_cannot_edit_teachers_comment(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);
        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['user_id' => $this->teacher->id, 'body' => 'Original']);

        $this->actingAs($this->student);
        $this->student->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('editCommentBody', 'Edited by student')
            ->call('updateComment', $comment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_comments', ['id' => $comment->id, 'body' => 'Original']);
    }

    public function test_comment_cannot_be_edited_or_deleted_outside_scheduled_session_window(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $pastSession = Session::factory()->for($this->course)->create([
            'date_start' => now()->subWeeks(2),
            'date_end' => now()->subWeek(),
        ]);
        $pastForum = Forum::factory()->for($this->course)->create(['session_id' => $pastSession->id]);
        $pastThread = ForumThread::factory()->for($pastForum)->create(['user_id' => $this->teacher->id]);
        $comment = ForumComment::factory()->for($pastThread, 'thread')->create(['user_id' => $this->teacher->id, 'body' => 'Original']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $pastThread])
            ->call('loadComments')
            ->set('editCommentBody', 'Too late')
            ->call('updateComment', $comment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_comments', ['id' => $comment->id, 'body' => 'Original']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $pastThread])
            ->call('loadComments')
            ->call('deleteComment', $comment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_comments', ['id' => $comment->id]);
    }

    public function test_dev_bypass_allows_edit_and_delete_outside_scheduled_window_in_local_env(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $pastSession = Session::factory()->for($this->course)->create([
            'date_start' => now()->subWeeks(2),
            'date_end' => now()->subWeek(),
        ]);
        $pastForum = Forum::factory()->for($this->course)->create(['session_id' => $pastSession->id]);
        $pastThread = ForumThread::factory()->for($pastForum)->create(['user_id' => $this->teacher->id]);
        $comment = ForumComment::factory()->for($pastThread, 'thread')->create(['user_id' => $this->teacher->id, 'body' => 'Original']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $pastThread])
            ->call('loadComments')
            ->set('devBypassEditDelete', true)
            ->set('editCommentBody', 'Now editable')
            ->call('updateComment', $comment->id);

        $this->assertDatabaseHas('forum_comments', ['id' => $comment->id, 'body' => 'Now editable']);
    }

    public function test_dev_bypass_has_no_effect_outside_local_or_testing_env(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $pastSession = Session::factory()->for($this->course)->create([
            'date_start' => now()->subWeeks(2),
            'date_end' => now()->subWeek(),
        ]);
        $pastForum = Forum::factory()->for($this->course)->create(['session_id' => $pastSession->id]);
        $pastThread = ForumThread::factory()->for($pastForum)->create(['user_id' => $this->teacher->id]);
        $comment = ForumComment::factory()->for($pastThread, 'thread')->create(['user_id' => $this->teacher->id, 'body' => 'Original']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $pastThread])
            ->call('loadComments')
            ->set('devBypassEditDelete', true)
            ->set('editCommentBody', 'Should not save')
            ->call('updateComment', $comment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('forum_comments', ['id' => $comment->id, 'body' => 'Original']);
    }

    public function test_owner_can_delete_thread_and_is_redirected(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('deleteThread')
            ->assertRedirect(route('forum.index', [$this->course, 'session' => $this->session->id]));

        $this->assertDatabaseMissing('forum_threads', ['id' => $this->thread->id]);
    }

    public function test_deleting_thread_removes_description_r2_attachments(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $this->thread->update([
            'description' => '<p><img src="https://r2.example.com/forum-attachments/cover.jpg"></p>',
        ]);

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('isManagedUrl')->andReturn(true);
            $mock->shouldReceive('delete')->once()->with('https://r2.example.com/forum-attachments/cover.jpg')->andReturn(true);
        });

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
            ->set('editCommentBody', 'Edited body')
            ->call('updateComment', $comment->id)
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
            ->set('newReplyBody', 'A reply')
            ->call('addReply', $comment->id)
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
            ->set('newReplyBody', 'Nested reply')
            ->call('addReply', $reply->id)
            ->assertStatus(404);
    }

    public function test_comment_supports_new_rich_text_tags_after_sanitization(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('newCommentBody', '<h1>Title</h1><blockquote>Quote</blockquote><img src="https://r2.example.com/photo.jpg">')
            ->call('addComment');

        $comment = $this->thread->comments()->latest()->first();

        $this->assertStringContainsString('<h1>Title</h1>', $comment->body);
        $this->assertStringContainsString('<blockquote>Quote</blockquote>', $comment->body);
        $this->assertStringContainsString('<img src="https://r2.example.com/photo.jpg"', $comment->body);
    }

    public function test_comment_strips_disallowed_tags(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('newCommentBody', '<script>alert(1)</script><div>Plain</div>')
            ->call('addComment');

        $comment = $this->thread->comments()->latest()->first();

        $this->assertStringNotContainsString('<script>', $comment->body);
        $this->assertStringNotContainsString('<div>', $comment->body);
    }

    public function test_delete_rich_text_attachment_removes_it_from_r2(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('isManagedUrl')->once()->with('https://r2.example.com/forum-attachments/photo.jpg')->andReturn(true);
            $mock->shouldReceive('delete')
                ->once()
                ->with('https://r2.example.com/forum-attachments/photo.jpg')
                ->andReturn(true);
        });

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('deleteRichTextAttachment', 'https://r2.example.com/forum-attachments/photo.jpg');
    }

    public function test_delete_rich_text_attachment_ignores_urls_outside_managed_bucket(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('isManagedUrl')->once()->with('https://evil.example.com/steal-this.jpg')->andReturn(false);
            $mock->shouldNotReceive('delete');
        });

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('deleteRichTextAttachment', 'https://evil.example.com/steal-this.jpg');
    }

    public function test_rich_text_image_upload_returns_public_url(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('uploadPublicFile')
                ->once()
                ->andReturn('https://r2.example.com/forum-attachments/photo.jpg');
        });

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->set('pendingRichTextFile', $file)
            ->call('insertRichTextFile')
            ->assertReturned('https://r2.example.com/forum-attachments/photo.jpg');
    }

    public function test_rich_text_pdf_upload_returns_public_url(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('uploadPublicFile')
                ->once()
                ->andReturn('https://r2.example.com/forum-attachments/document.pdf');
        });

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->set('pendingRichTextFile', $file)
            ->call('insertRichTextFile')
            ->assertReturned('https://r2.example.com/forum-attachments/document.pdf');
    }

    public function test_rich_text_zip_upload_returns_public_url(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('uploadPublicFile')
                ->once()
                ->andReturn('https://r2.example.com/forum-attachments/archive.zip');
        });

        $file = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->set('pendingRichTextFile', $file)
            ->call('insertRichTextFile')
            ->assertReturned('https://r2.example.com/forum-attachments/archive.zip');
    }

    public function test_rich_text_file_upload_rejects_disallowed_file_type(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $file = UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream');

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->set('pendingRichTextFile', $file)
            ->call('insertRichTextFile')
            ->assertHasErrors(['pendingRichTextFile']);
    }

    public function test_comment_list_can_be_sorted_by_every_option(): void
    {
        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        $comment = ForumComment::factory()->for($this->thread, 'thread')->create(['likes_count' => 1]);
        ForumComment::factory()->for($this->thread, 'thread')->create(['parent_id' => $comment->id, 'likes_count' => 2]);

        foreach (['latest_comment', 'oldest_comment', 'most_liked_comment', 'latest_reply', 'oldest_reply', 'most_liked_reply'] as $sortBy) {
            Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
                ->call('loadComments')
                ->set('sortBy', $sortBy)
                ->assertOk();
        }
    }

    public function test_mounting_with_comment_query_param_jumps_to_the_correct_page(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        // Default sort is 'latest_comment' (newest first) with perPage=10.
        // The oldest of 11 top-level comments sits at index 10, i.e. page 2.
        $target = ForumComment::factory()->for($this->thread, 'thread')->create(['created_at' => now()->subDay()]);
        ForumComment::factory()->for($this->thread, 'thread')->count(10)->create();

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread, 'highlightCommentId' => $target->id])
            ->assertSet('page', 2);
    }

    public function test_mounting_with_a_reply_comment_query_param_jumps_to_its_parents_page(): void
    {
        $this->teacher->givePermissionTo('forum.view');

        $parent = ForumComment::factory()->for($this->thread, 'thread')->create(['created_at' => now()->subDay()]);
        $reply = ForumComment::factory()->for($this->thread, 'thread')->create(['parent_id' => $parent->id]);
        ForumComment::factory()->for($this->thread, 'thread')->count(10)->create();

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread, 'highlightCommentId' => $reply->id])
            ->assertSet('page', 2);
    }

    public function test_generate_comments_bulk_creates_fake_comments_in_local_env(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('generateCommentCount', 4)
            ->call('generateComments');

        $this->assertSame(4, ForumComment::where('thread_id', $this->thread->id)->where('user_id', $this->teacher->id)->count());
    }

    public function test_generate_comments_caps_count_at_fifty(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->set('generateCommentCount', 999)
            ->call('generateComments');

        $this->assertSame(50, ForumComment::where('thread_id', $this->thread->id)->count());
    }

    public function test_generate_comments_is_forbidden_outside_local_or_testing_env(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->teacher->givePermissionTo(['forum.view', 'forum.create']);

        Livewire::test(ForumThreadShow::class, ['course' => $this->course, 'thread' => $this->thread])
            ->call('loadComments')
            ->call('generateComments')
            ->assertStatus(403);
    }
}
