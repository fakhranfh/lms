<?php

use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\Session;
use App\Services\ForumCommentLikeService;
use App\Services\ForumCommentService;
use App\Services\ForumService;
use App\Services\SessionService;

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

test('creating a session auto-creates its linked forum', function () {
    $course = Course::factory()->create();

    $session = app(SessionService::class)->create(
        Session::factory()->for($course)->raw()
    );

    $forum = Forum::where('session_id', $session->id)->first();

    expect($forum)->not->toBeNull();
    expect($forum->course_id)->toBe($course->id);
});

test('findOrCreateForSession is idempotent for a session', function () {
    $course = Course::factory()->create();
    $session = Session::factory()->for($course)->create();
    $service = app(ForumService::class);

    $first = $service->findOrCreateForSession($session->id, $course->id);
    $second = $service->findOrCreateForSession($session->id, $course->id);

    expect($second->id)->toBe($first->id);
    expect(Forum::where('session_id', $session->id)->count())->toBe(1);
});
