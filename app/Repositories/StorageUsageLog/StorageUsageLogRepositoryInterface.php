<?php

namespace App\Repositories\StorageUsageLog;

use App\Models\StorageUsageLog;
use Illuminate\Support\Collection;

interface StorageUsageLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StorageUsageLog;

    /**
     * Find the most recent global (school_id null) usage log.
     */
    public function findLatestGlobal(): ?StorageUsageLog;

    /**
     * Get global usage logs from the last N days, oldest first.
     *
     * @return Collection<int, StorageUsageLog>
     */
    public function getGlobalTrend(int $days): Collection;
}
