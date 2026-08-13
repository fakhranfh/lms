<?php

namespace App\Services;

use App\Enums\DeliveryMode;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Session;
use Illuminate\Support\Collection;

/**
 * Derives a Forum Discussion-type Assessment's score for a student directly
 * from forum participation data — no builder, no manual submit (same
 * "derived" pattern as Attendance). Score = (sessions where the student met
 * required_forum_posts / sessions in scope) * weight.
 *
 * Scoping: only online-delivery sessions are counted, since
 * `required_forum_posts` is only meaningful for that delivery mode (mirrors
 * AttendanceDerivationService::hasMetForumPostRequirement()). Within those
 * sessions, ones whose date_start falls within the assessment's
 * [start_date, end_date] window are used; if none fall in that window, all
 * online course sessions are used.
 */
class ForumDiscussionScoringService
{
    private const DEFAULT_REQUIRED_FORUM_POSTS = 2;

    public function __construct(
        private SessionService $sessionService,
        private ForumThreadService $forumThreadService,
        private ForumCommentService $forumCommentService,
        private AssessmentAttemptService $assessmentAttemptService,
        private AssessmentScoreService $assessmentScoreService,
    ) {}

    /**
     * @return array{met: int, total: int, percentage: float, score: float}
     */
    public function computeForUser(Assessment $assessment, string $userId): array
    {
        $sessions = $this->sessionsInScope($assessment);

        $met = $sessions->filter(
            fn (Session $session) => $this->hasMetForumPostRequirement($session, $userId)
        )->count();

        $total = $sessions->count();
        $percentage = $total > 0 ? $met / $total : 0.0;
        $score = round($percentage * $assessment->weight, 2);

        return [
            'met' => $met,
            'total' => $total,
            'percentage' => round($percentage * 100, 1),
            'score' => $score,
        ];
    }

    /**
     * Derives the score and writes/updates a single AssessmentAttempt +
     * AssessmentScore for the user, mirroring AttendanceScoringService's
     * "single row per user" pattern.
     */
    public function recomputeForUser(Assessment $assessment, string $userId): AssessmentAttempt
    {
        $computed = $this->computeForUser($assessment, $userId);

        $attempts = $this->assessmentAttemptService->forAssessmentAndUser($assessment->id, $userId);
        $attempt = $attempts->first();

        if (! $attempt) {
            $attempt = $this->assessmentAttemptService->create([
                'assessment_id' => $assessment->id,
                'user_id' => $userId,
                'submitted_by' => $userId,
                'attempt_number' => 1,
                'started_at' => now(),
                'submitted_at' => now(),
            ]);
        }

        $existingScore = $this->assessmentScoreService->findByAttempt($attempt->id);
        $data = ['assessment_attempt_id' => $attempt->id, 'score' => $computed['score']];

        if ($existingScore) {
            $this->assessmentScoreService->update($existingScore->id, $data);
        } else {
            $this->assessmentScoreService->create($data);
        }

        return $attempt;
    }

    public function hasMetForumPostRequirement(Session $session, string $userId): bool
    {
        $threadCount = $this->forumThreadService->countForUserInSession($userId, $session->id);
        $commentCount = $this->forumCommentService->countForUserInSession($userId, $session->id);

        return ($threadCount + $commentCount) >= $this->requiredForumPosts($session);
    }

    public function requiredForumPosts(Session $session): int
    {
        return $session->required_forum_posts ?: self::DEFAULT_REQUIRED_FORUM_POSTS;
    }

    /**
     * @return Collection<int, Session>
     */
    public function sessionsInScope(Assessment $assessment): Collection
    {
        $sessions = $this->sessionService->forCourse($assessment->course_id)
            ->filter(fn (Session $session) => $session->delivery_mode === DeliveryMode::Online)
            ->values();

        if (! $assessment->start_date || ! $assessment->end_date) {
            return $sessions;
        }

        $inRange = $sessions->filter(
            fn (Session $session) => $session->date_start->between($assessment->start_date, $assessment->end_date)
        );

        return $inRange->isNotEmpty() ? $inRange->values() : $sessions;
    }
}
