<?php

use App\Enums\DeliveryMode;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\Session;
use App\Models\User;
use App\Services\ForumDiscussionScoringService;

test('computeForUser derives percentage and score from online sessions in the assessment date range', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create([
        'type' => 'forum_discussion',
        'weight' => 10,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(5),
    ]);

    $inRangeMet = Session::factory()->create(['course_id' => $course->id, 'date_start' => now(), 'delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    $forumMet = Forum::factory()->create(['course_id' => $course->id, 'session_id' => $inRangeMet->id]);
    $thread = ForumThread::factory()->create(['forum_id' => $forumMet->id, 'user_id' => $user->id]);
    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

    $inRangeMissed = Session::factory()->create(['course_id' => $course->id, 'date_start' => now()->addDay(), 'delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    Forum::factory()->create(['course_id' => $course->id, 'session_id' => $inRangeMissed->id]);

    // Out of range — should not count.
    $outOfRange = Session::factory()->create(['course_id' => $course->id, 'date_start' => now()->addMonths(2), 'delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    Forum::factory()->create(['course_id' => $course->id, 'session_id' => $outOfRange->id]);

    $service = app(ForumDiscussionScoringService::class);
    $computed = $service->computeForUser($assessment, $user->id);

    expect($computed['total'])->toBe(2)
        ->and($computed['met'])->toBe(1)
        ->and($computed['percentage'])->toBe(50.0)
        ->and($computed['score'])->toBe(5.0);
});

test('virtual_class and offline sessions are excluded from the forum discussion scoring scope, only online counts', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create([
        'type' => 'forum_discussion',
        'weight' => 10,
        'start_date' => null,
        'end_date' => null,
    ]);

    $online = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    $forum = Forum::factory()->create(['course_id' => $course->id, 'session_id' => $online->id]);
    $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

    Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::Offline]);

    $service = app(ForumDiscussionScoringService::class);
    $computed = $service->computeForUser($assessment, $user->id);

    expect($computed['total'])->toBe(1)
        ->and($computed['met'])->toBe(1);
});

test('recomputeForUser writes a single attempt and score row that updates on recompute', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create([
        'type' => 'forum_discussion',
        'weight' => 10,
        'start_date' => null,
        'end_date' => null,
    ]);

    $session = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    $forum = Forum::factory()->create(['course_id' => $course->id, 'session_id' => $session->id]);
    $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

    $service = app(ForumDiscussionScoringService::class);
    $attempt = $service->recomputeForUser($assessment, $user->id);

    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 10]);
    $this->assertDatabaseCount('assessment_attempts', 1);

    ForumComment::where('thread_id', $thread->id)->delete();
    $service->recomputeForUser($assessment, $user->id);

    $this->assertDatabaseCount('assessment_attempts', 1);
    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 0]);
});
