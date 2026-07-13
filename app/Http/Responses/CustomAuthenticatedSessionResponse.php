<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse;

class CustomAuthenticatedSessionResponse implements LoginResponse
{
    public function toResponse($request)
    {
        if ($request->user() && ! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
