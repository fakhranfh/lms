<?php

namespace App\Listeners;

use App\Jobs\DetectUserTimezoneJob;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class UpdateUserTimezoneOnLogin
{
    public function __construct(
        private Request $request,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        DetectUserTimezoneJob::dispatch($user->id, $this->request->ip());
    }
}
