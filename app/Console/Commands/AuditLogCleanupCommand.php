<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AuditLogCleanupCommand extends Command
{
    protected $signature = 'audit-logs:cleanup {--older-than=1-year : Retention window, e.g. "1-year", "90-days"}';

    protected $description = 'Delete audit logs older than the configured retention window';

    public function handle(): int
    {
        $cutoff = $this->resolveCutoff($this->option('older-than'));

        $deleted = AuditLog::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} audit log(s) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }

    private function resolveCutoff(string $olderThan): Carbon
    {
        [$amount, $unit] = array_pad(explode('-', $olderThan, 2), 2, 'year');

        $amount = (int) $amount;
        $unit = rtrim($unit, 's');

        return match ($unit) {
            'day' => now()->subDays($amount),
            'month' => now()->subMonths($amount),
            'year' => now()->subYears($amount),
            default => now()->subYear(),
        };
    }
}
