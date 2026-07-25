<?php

namespace App\Repositories\StorageUsageLog;

use App\Models\StorageUsageLog;
use Illuminate\Support\Collection;

class StorageUsageLogRepository implements StorageUsageLogRepositoryInterface
{
    public function create(array $data): StorageUsageLog
    {
        return StorageUsageLog::create($data);
    }

    public function findLatestGlobal(): ?StorageUsageLog
    {
        return StorageUsageLog::whereNull('school_id')->latest('id')->first();
    }

    public function getGlobalTrend(int $days): Collection
    {
        return StorageUsageLog::whereNull('school_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();
    }
}
