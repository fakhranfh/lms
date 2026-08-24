<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\IpGeolocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DetectUserTimezoneJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $userId,
        private readonly ?string $ip,
    ) {}

    public function handle(IpGeolocationService $ipGeolocationService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $timezone = $ipGeolocationService->detectTimezone($this->ip);

        if ($timezone && $timezone !== $user->timezone) {
            $user->update(['timezone' => $timezone]);
        }
    }
}
