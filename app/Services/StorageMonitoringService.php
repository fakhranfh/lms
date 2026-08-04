<?php

namespace App\Services;

use App\Models\School;
use App\Models\StorageUsageLog;
use App\Repositories\MediaLibrary\MediaLibraryRepositoryInterface;
use App\Repositories\School\SchoolRepositoryInterface;
use App\Repositories\StorageUsageLog\StorageUsageLogRepositoryInterface;
use Illuminate\Support\Collection;

class StorageMonitoringService
{
    /** @var array<int, int> Thresholds checked in descending order for alerting */
    public const ALERT_THRESHOLDS = [100, 90, 80];

    public function __construct(
        protected R2StorageService $r2Service,
        protected MediaLibraryRepositoryInterface $mediaLibraryRepository,
        protected StorageUsageLogRepositoryInterface $usageLogRepository,
        protected SchoolRepositoryInterface $schoolRepository,
    ) {}

    /**
     * Global storage summary across all schools, based on media library items in the database.
     *
     * @return array{used_bytes: int, quota_bytes: int, percentage: float, used_formatted: string, quota_formatted: string}
     */
    public function globalSummary(): array
    {
        $usedBytes = $this->mediaLibraryRepository->sumFileSize();
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
     * Per-school storage breakdown, aggregated from media library items.
     *
     * Each item is shaped: array{school: School, used_bytes: int, material_count: int, largest_material_bytes: int, last_upload_at: string|null}
     */
    public function perSchoolBreakdown(string $sortBy = 'used_bytes', string $direction = 'desc'): Collection
    {
        $rows = $this->schoolRepository->getAllWithUserCounts()
            ->map(function (School $school): array {
                $items = $this->mediaLibraryRepository->getForSchool($school->id);
                $lastUploadAt = $items->max('created_at');

                return [
                    'school' => $school,
                    'used_bytes' => (int) $items->sum('file_size'),
                    'material_count' => count($items),
                    'largest_material_bytes' => (int) $items->max('file_size'),
                    'last_upload_at' => $lastUploadAt !== null ? (string) $lastUploadAt : null,
                ];
            });

        return $rows->sortBy($sortBy, SORT_REGULAR, $direction === 'desc')
            ->values()
            ->map(fn (array $row): array => $row);
    }

    /**
     * Record a snapshot of global storage usage and return the alert threshold newly crossed, if any.
     */
    public function logGlobalUsageAndGetNewThreshold(): ?int
    {
        $summary = $this->globalSummary();

        $lastLog = $this->usageLogRepository->findLatestGlobal();
        $lastThreshold = $lastLog->last_alert_threshold ?? 0;

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
