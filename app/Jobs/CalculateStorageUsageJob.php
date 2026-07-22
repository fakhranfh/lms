<?php

namespace App\Jobs;

use App\Mail\StorageQuotaAlertMail;
use App\Models\User;
use App\Services\StorageMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class CalculateStorageUsageJob implements ShouldQueue
{
    use Queueable;

    public function handle(StorageMonitoringService $monitoringService): void
    {
        $threshold = $monitoringService->logGlobalUsageAndGetNewThreshold();

        if ($threshold === null) {
            return;
        }

        $summary = $monitoringService->globalSummary();

        $recipients = User::permission('settings.school')->get()->unique('id');

        foreach ($recipients as $admin) {
            Mail::to($admin->email)->queue(new StorageQuotaAlertMail(
                threshold: $threshold,
                usedFormatted: $summary['used_formatted'],
                quotaFormatted: $summary['quota_formatted'],
                percentage: $summary['percentage'],
            ));
        }
    }
}
