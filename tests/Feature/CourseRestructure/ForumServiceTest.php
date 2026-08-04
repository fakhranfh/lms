<?php

use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Services\ForumCommentLikeService;
use App\Services\ForumCommentService;

test('creating a comment increments the thread comments_count', function () {
    $thread = ForumThread::factory()->create();

    app(ForumCommentService::class)->create([
        'thread_id' => $thread->id,
        'user_id' => $thread->user_id,
        'body' => 'Great point!',
    ]);

    expect($thread->fresh()->comments_count)->toBe(1);
});

test('deleting a comment decrements the thread comments_count', function () {
    $thread = ForumThread::factory()->create(['comments_count' => 1]);
    $comment = ForumComment::factory()->for($thread, 'thread')->create();

    app(ForumCommentService::class)->delete($comment->id);

    expect($thread->fresh()->comments_count)->toBe(0);
});

test('liking a comment increments likes_count and unliking decrements it', function () {
    $comment = ForumComment::factory()->create();
    $service = app(ForumCommentLikeService::class);

    $like = $service->create([
        'comment_id' => $comment->id,
        'user_id' => $comment->user_id,
    ]);

    expect($comment->fresh()->likes_count)->toBe(1);

    $service->delete($like->id);

    expect($comment->fresh()->likes_count)->toBe(0);
});

test('force deleting a course cascades to its forums and threads', function () {
    $forum = Forum::factory()->create();
    $thread = ForumThread::factory()->for($forum)->create();
    $course = $forum->course;

    $course->forceDelete();

    expect(Forum::find($forum->id))->toBeNull();
    expect(ForumThread::find($thread->id))->toBeNull();
});
