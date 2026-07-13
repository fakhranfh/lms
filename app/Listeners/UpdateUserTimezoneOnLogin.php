<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\IpGeolocationService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class UpdateUserTimezoneOnLogin
{
    public function __construct(
        private IpGeolocationService $ipGeolocationService,
        private Request $request,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $timezone = $this->ipGeolocationService->detectTimezone($this->request->ip());

        if ($timezone && $timezone !== $user->timezone) {
            $user->update(['timezone' => $timezone]);
        }
    }
}
