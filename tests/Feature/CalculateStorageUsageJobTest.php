<?php

use App\Jobs\CalculateStorageUsageJob;
use App\Mail\StorageQuotaAlertMail;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use App\Services\StorageMonitoringService;
use Illuminate\Support\Facades\Mail;

function fillGlobalStorageTo(float $fraction): void
{
    $quotaBytes = app(StorageMonitoringService::class)->globalSummary()['quota_bytes'];
    $totalBytes = (int) ($quotaBytes * $fraction);
    $chunk = 300 * 1024 * 1024;

    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $module = Module::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module)->create();

    while ($totalBytes > 0) {
        $size = min($chunk, $totalBytes);
        LessonMaterial::factory()->withLesson($lesson)->create(['file_size' => $size]);
        $totalBytes -= $size;
    }
}

test('job emails admins with settings.school permission when a threshold is first crossed', function () {
    Mail::fake();

    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    fillGlobalStorageTo(0.85);

    (new CalculateStorageUsageJob)->handle(app(StorageMonitoringService::class));

    Mail::assertQueued(StorageQuotaAlertMail::class, function ($mail) use ($admin) {
        return $mail->threshold === 80 && $mail->hasTo($admin->email);
    });
});

test('job does not email again when threshold was already alerted', function () {
    Mail::fake();

    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    fillGlobalStorageTo(0.85);

    $service = app(StorageMonitoringService::class);
    (new CalculateStorageUsageJob)->handle($service);
    $firstRoundCount = count(Mail::queued(StorageQuotaAlertMail::class));

    (new CalculateStorageUsageJob)->handle($service);
    $secondRoundCount = count(Mail::queued(StorageQuotaAlertMail::class));

    expect($secondRoundCount)->toBe($firstRoundCount);
});

test('job sends no email when no threshold is crossed', function () {
    Mail::fake();

    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    fillGlobalStorageTo(0.10);

    (new CalculateStorageUsageJob)->handle(app(StorageMonitoringService::class));

    Mail::assertNothingQueued();
});
