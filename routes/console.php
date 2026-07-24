<?php

use App\Jobs\CalculateStorageUsageJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CalculateStorageUsageJob)->hourly();

Schedule::command('audit-logs:cleanup')->monthly();

Schedule::command('horizon:snapshot')->everyFiveMinutes();
