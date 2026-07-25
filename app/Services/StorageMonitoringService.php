<?php

namespace App\Services;

use App\Models\School;
use App\Models\StorageUsageLog;
use App\Repositories\LessonMaterial\LessonMaterialRepositoryInterface;
use App\Repositories\School\SchoolRepositoryInterface;
use App\Repositories\StorageUsageLog\StorageUsageLogRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StorageMonitoringService
{
    /** @var array<int, int> Thresholds checked in descending order for alerting */
    public const ALERT_THRESHOLDS = [100, 90, 80];

    public function __construct(
        protected R2StorageService $r2Service,
        protected LessonMaterialRepositoryInterface $materialRepository,
        protected StorageUsageLogRepositoryInterface $usageLogRepository,
        protected SchoolRepositoryInterface $schoolRepository,
    ) {}

    /**
     * Global storage summary across all schools, based on active materials in the database.
     *
     * @return array{used_bytes: int, quota_bytes: int, percentage: float, used_formatted: string, quota_formatted: string}
     */
    public function globalSummary(): array
    {
        $usedBytes = $this->materialRepository->sumActiveFileSize();
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
        return $this->usageLogRepository->getGlobalTrend($days);
    }

    /**
     * Per-school storage breakdown, aggregated from active lesson materials.
     *
     * @return Collection<int, array{school: School, used_bytes: int, material_count: int, largest_material_bytes: int, last_upload_at: ?string}>
     */
    public function perSchoolBreakdown(string $sortBy = 'used_bytes', string $direction = 'desc'): Collection
    {
        $rows = $this->schoolRepository->getAllWithUserCounts()
            ->map(function (School $school) {
                $materials = $this->materialRepository->getActiveForSchool($school->id);

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
        return $this->materialRepository->filteredQuery($filters, $sortBy, $sortDirection);
    }

    /**
     * Record a snapshot of global storage usage and return the alert threshold newly crossed, if any.
     */
    public function logGlobalUsageAndGetNewThreshold(): ?int
    {
        $summary = $this->globalSummary();

        $lastLog = $this->usageLogRepository->findLatestGlobal();
        $lastThreshold = $lastLog?->last_alert_threshold ?? 0;

        $crossedThreshold = null;
        foreach (self::ALERT_THRESHOLDS as $threshold) {
            if ($summary['percentage'] >= $threshold && $lastThreshold < $threshold) {
                $crossedThreshold = $threshold;
                break;
            }
        }

        $this->usageLogRepository->create([
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
