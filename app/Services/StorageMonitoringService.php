<?php

namespace App\Services;

use App\Models\LessonMaterial;
use App\Models\School;
use App\Models\StorageUsageLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StorageMonitoringService
{
    /** @var array<int, int> Thresholds checked in descending order for alerting */
    public const ALERT_THRESHOLDS = [100, 90, 80];

    public function __construct(protected R2StorageService $r2Service) {}

    /**
     * Global storage summary across all schools, based on active materials in the database.
     *
     * @return array{used_bytes: int, quota_bytes: int, percentage: float, used_formatted: string, quota_formatted: string}
     */
    public function globalSummary(): array
    {
        $usedBytes = (int) LessonMaterial::active()->sum('file_size');
        $quotaBytes = $this->r2Service->getGlobalQuotaBytes();
        $percentage = $quotaBytes > 0 ? round(($usedBytes / $quotaBytes) * 100, 2) : 0.0;

        return [
            'used_bytes' => $usedBytes,
            'quota_bytes' => $quotaBytes,
            'percentage' => $percentage,
            'used_formatted' => R2StorageService::formatBytes($usedBytes),
            'quota_formatted' => R2StorageService::formatBytes($quotaBytes),
        ];
    }

    /**
     * Storage usage trend for the last N days, based on logged snapshots.
     *
     * @return Collection<int, StorageUsageLog>
     */
    public function usageTrend(int $days = 30): Collection
    {
        return StorageUsageLog::whereNull('school_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Per-school storage breakdown, aggregated from active lesson materials.
     *
     * @return Collection<int, array{school: School, used_bytes: int, material_count: int, largest_material_bytes: int, last_upload_at: ?string}>
     */
    public function perSchoolBreakdown(string $sortBy = 'used_bytes', string $direction = 'desc'): Collection
    {
        $rows = School::query()
            ->withCount(['users'])
            ->get()
            ->map(function (School $school) {
                $materials = LessonMaterial::active()
                    ->whereHas('lesson.module.course', fn ($q) => $q->where('school_id', $school->id))
                    ->get();

                return [
                    'school' => $school,
                    'used_bytes' => (int) $materials->sum('file_size'),
                    'material_count' => $materials->count(),
                    'largest_material_bytes' => (int) $materials->max('file_size'),
                    'last_upload_at' => $materials->max('created_at'),
                ];
            });

        return $rows->sortBy($sortBy, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Active materials matching the given filters, for the school materials browser page.
     *
     * @param  array{school_id?: ?string, course_id?: ?string, module_id?: ?string, lesson_id?: ?string, title?: ?string}  $filters
     */
    public function filteredMaterialsQuery(array $filters, string $sortBy = 'created_at', string $sortDirection = 'desc'): Builder
    {
        return LessonMaterial::active()
            ->when($filters['school_id'] ?? null, fn (Builder $q, string $v) => $q->whereHas(
                'lesson.module.course', fn ($qq) => $qq->where('school_id', $v)
            ))
            ->when($filters['course_id'] ?? null, fn (Builder $q, string $v) => $q->whereHas(
                'lesson.module', fn ($qq) => $qq->where('course_id', $v)
            ))
            ->when($filters['module_id'] ?? null, fn (Builder $q, string $v) => $q->whereHas(
                'lesson', fn ($qq) => $qq->where('module_id', $v)
            ))
            ->when($filters['lesson_id'] ?? null, fn (Builder $q, string $v) => $q->where('lesson_id', $v))
            ->when($filters['title'] ?? null, fn (Builder $q, string $v) => $q->whereLike('title', "%{$v}%", caseSensitive: false))
            ->with('lesson.module.course.school')
            ->orderBy($sortBy, $sortDirection);
    }

    /**
     * Record a snapshot of global storage usage and return the alert threshold newly crossed, if any.
     */
    public function logGlobalUsageAndGetNewThreshold(): ?int
    {
        $summary = $this->globalSummary();

        $lastLog = StorageUsageLog::whereNull('school_id')->latest('id')->first();
        $lastThreshold = $lastLog?->last_alert_threshold ?? 0;

        $crossedThreshold = null;
        foreach (self::ALERT_THRESHOLDS as $threshold) {
            if ($summary['percentage'] >= $threshold && $lastThreshold < $threshold) {
                $crossedThreshold = $threshold;
                break;
            }
        }

        StorageUsageLog::create([
            'school_id' => null,
            'total_used_bytes' => $summary['used_bytes'],
            'quota_bytes' => $summary['quota_bytes'],
            'usage_percent' => $summary['percentage'],
            'last_alert_threshold' => $crossedThreshold ?? max($lastThreshold, $this->currentThresholdBand($summary['percentage'])),
        ]);

        return $crossedThreshold;
    }

    /**
     * The highest threshold band the given percentage currently sits in (without requiring a "new" crossing).
     */
    protected function currentThresholdBand(float $percentage): int
    {
        foreach (self::ALERT_THRESHOLDS as $threshold) {
            if ($percentage >= $threshold) {
                return $threshold;
            }
        }

        return 0;
    }
}
