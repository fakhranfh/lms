<?php

namespace App\Livewire\Courses\Concerns;

use App\Enums\AssessmentType;
use Carbon\Carbon;

/**
 * Shared blade-ready view model builders for the Gradebook's per-user
 * breakdown (final score banner + Assessment Type accordion), used by both
 * GradebookIndex (student's own breakdown) and GradebookShow (teacher's
 * per-student detail page) so the two components stay in lockstep.
 */
trait BuildsGradebookViewData
{
    /**
     * @param  array{final: array, types: array<int, array{type: AssessmentType, weight: float, assessment_id: ?string, score: ?float, last_updated_at: ?Carbon, sessions: array}>}  $result
     * @return array<int, array{key: string, label: string, weight: float, assessment_id: ?string, score: ?float, last_updated_label: ?string, expandable: bool, sessions_url: ?string}>
     */
    private function typeRows(array $result, ?string $studentId): array
    {
        return collect($result['types'])->map(function (array $typeRow) use ($studentId) {
            $key = $typeRow['type']->value;
            $isSessionBased = in_array($key, ['attendance', 'forum_discussion'], true);
            $expandable = $isSessionBased ? count($typeRow['sessions']) > 0 : true;

            return [
                'key' => $key,
                'label' => $this->typeLabel($key),
                'weight' => $typeRow['weight'],
                'assessment_id' => $typeRow['assessment_id'] ?? null,
                'score' => $typeRow['score'],
                'last_updated_label' => $this->lastUpdatedLabel($typeRow['last_updated_at']),
                'expandable' => $expandable,
                'sessions_url' => $expandable
                    ? route('gradebook.sessions', [$this->course, $key]).($studentId ? '?student_id='.$studentId : '')
                    : null,
            ];
        })->all();
    }

    private function typeLabel(string $key): string
    {
        if ($key === 'theory_final_exam') {
            return 'THEORY: FINAL EXAM';
        }

        if (str($key)->startsWith('theory_')) {
            return 'THEORY: '.str($key)->after('theory_')->replace('_', ' ')->title();
        }

        return str($key)->replace('_', ' ')->title()->toString();
    }

    private function lastUpdatedLabel(?Carbon $lastUpdatedAt): ?string
    {
        if ($lastUpdatedAt === null) {
            return null;
        }

        $timezone = auth()->user()->timezone ?: config('app.timezone');
        $viewerDate = $lastUpdatedAt->clone()->setTimezone($timezone);

        $offsetMinutes = $viewerDate->utcOffset();
        $sign = $offsetMinutes < 0 ? '-' : '+';
        $hours = intdiv(abs($offsetMinutes), 60);
        $minutes = abs($offsetMinutes) % 60;
        $gmtOffset = $sign.$hours.($minutes > 0 ? ':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');

        return $viewerDate->translatedFormat('j M Y, H:i').' GMT'.$gmtOffset;
    }

    private function letterGrade(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= $this->course->grade_band_a_min => 'A',
            $score >= $this->course->grade_band_b_min => 'B',
            $score >= $this->course->grade_band_c_min => 'C',
            $score >= $this->course->grade_band_d_min => 'D',
            default => 'E',
        };
    }
}
