<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Tests\TestCase;

class ForumCommentLikeControllerTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private ForumComment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();

        $course = Course::factory()->for($this->school)->create();
        $session = Session::factory()->for($course)->create();
        $forum = Forum::factory()->for($course)->create(['session_id' => $session->id]);
        $thread = ForumThread::factory()->for($forum)->create(['user_id' => $this->teacher->id]);
        $this->comment = ForumComment::factory()->for($thread, 'thread')->create();
    }

    public function test_toggling_like_creates_then_removes_it(): void
    {
        $this->teacher->givePermissionTo('forum.create');
        $this->actingAs($this->teacher);

        $response = $this->postJson(route('forum.comment.toggle-like', $this->comment->id));

        $response->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $this->assertSame(1, $this->comment->fresh()->likes_count);

        $response = $this->postJson(route('forum.comment.toggle-like', $this->comment->id));

        $response->assertOk()->assertJson(['liked' => false, 'count' => 0]);
        $this->assertSame(0, $this->comment->fresh()->likes_count);
    }

    public function test_user_without_permission_cannot_toggle_like(): void
    {
        $this->actingAs($this->student);

        $this->postJson(route('forum.comment.toggle-like', $this->comment->id))
            ->assertStatus(403);
    }

    public function test_user_from_different_school_cannot_toggle_like(): void
    {
        $otherSchool = School::factory()->create();
        $otherUser = User::factory()->forSchool($otherSchool)->create();
        $otherUser->givePermissionTo('forum.create');

        $this->actingAs($otherUser);

        $this->postJson(route('forum.comment.toggle-like', $this->comment->id))
            ->assertStatus(403);
    }
}
