<?php

namespace App\Services;

use App\Enums\DeliveryMode;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Session;
use Illuminate\Support\Collection;

/**
 * Derives an Attendance-type Assessment's score for a student directly from
 * attendance data — no builder, no manual submit (same "derived" pattern as
 * Forum Discussion). Score = (sessions attended / sessions in scope) * weight.
 *
 * Scoping: only virtual_class sessions are counted (video conference join or
 * manual mark) — offline and online sessions are excluded from this
 * assessment score, though they still appear on the general Attendance page.
 * Within virtual_class sessions, those whose date_start falls within the
 * assessment's [start_date, end_date] window are used; if none fall in that
 * window (e.g. the assessment spans the whole course), all virtual_class
 * course sessions are used. This lets a Teacher scope an Attendance
 * assessment to part of a term while still working for a course-wide one.
 */
class AttendanceScoringService
{
    public function __construct(
        private SessionService $sessionService,
        private AttendanceDerivationService $attendanceDerivationService,
        private AssessmentAttemptService $assessmentAttemptService,
        private AssessmentScoreService $assessmentScoreService,
    ) {}

    /**
     * @return array{attended: int, total: int, percentage: float, score: float}
     */
    public function computeForUser(Assessment $assessment, string $userId): array
    {
        $sessions = $this->sessionsInScope($assessment);

        $attended = $sessions->filter(
            fn (Session $session) => $this->attendanceDerivationService->isSessionAttended($session, $userId)
        )->count();

        $total = $sessions->count();
        $percentage = $total > 0 ? $attended / $total : 0.0;
        $score = round($percentage * $assessment->weight, 2);

        return [
            'attended' => $attended,
            'total' => $total,
            'percentage' => round($percentage * 100, 1),
            'score' => $score,
        ];
    }

    /**
     * Derives the score and writes/updates a single AssessmentAttempt +
     * AssessmentScore for the user, mirroring QuizAttemptScoringService's
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

    /**
     * @return Collection<int, Session>
     */
    public function sessionsInScope(Assessment $assessment): Collection
    {
        $sessions = $this->sessionService->forCourse($assessment->course_id, ['videoConferences.participations'])
            ->filter(fn (Session $session) => $session->delivery_mode === DeliveryMode::VirtualClass)
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
