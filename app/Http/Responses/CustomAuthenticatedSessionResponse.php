<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse;

class CustomAuthenticatedSessionResponse implements LoginResponse
{
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // TODO: the school-payment checkout flow was removed. Previously,
        // a user with a pending paid-tier registration transaction was
        // redirected here to resume checkout (school.payment.index). That
        // route no longer exists, so pending paid-tier registrations are
        // currently unreachable after login. Revisit once a replacement
        // payment flow is decided.

        return redirect()->intended(config('fortify.home'));
    }
}
