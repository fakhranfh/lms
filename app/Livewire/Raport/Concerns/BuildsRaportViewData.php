<?php

namespace App\Livewire\Raport\Concerns;

use App\Enums\AssessmentType;
use App\Models\Course;
use Carbon\Carbon;

/**
 * Shared blade-ready view model builders for the Raport (report card), which
 * summarizes a Gradebook score computation without the interactive session
 * drill-down GradebookIndex/GradebookShow expose.
 */
trait BuildsRaportViewData
{
    /**
     * Lists every AssessmentType, not just the ones the course has actually
     * configured an Assessment for — GradebookScoringService::computeForUser()
     * omits a type entirely when the course has no Assessment of that type
     * yet, but the Raport is meant to read as a full report card, so an
     * unconfigured type (e.g. no Final Exam created yet) still gets a row,
     * shown with a blank weight/score rather than being left out.
     *
     * @param  array{final: array, types: array<int, array{type: AssessmentType, weight: float, score: ?float, last_updated_at: ?Carbon}>}  $result
     * @return array<int, array{key: string, label: string, weight: ?float, score: ?float}>
     */
    private function typeRows(array $result): array
    {
        $configured = collect($result['types'])->keyBy(fn (array $typeRow) => $typeRow['type']->value);

        return collect(AssessmentType::cases())->map(function (AssessmentType $type) use ($configured) {
            $typeRow = $configured->get($type->value);

            return [
                'key' => $type->value,
                'label' => $this->typeLabel($type->value),
                'weight' => $typeRow['weight'] ?? null,
                'score' => $typeRow['score'] ?? null,
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

    private function letterGrade(Course $course, ?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= $course->grade_band_a_min => 'A',
            $score >= $course->grade_band_b_min => 'B',
            $score >= $course->grade_band_c_min => 'C',
            $score >= $course->grade_band_d_min => 'D',
            default => 'E',
        };
    }
}
