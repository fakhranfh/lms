<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;

class CustomPasswordResetLinkResponse implements SuccessfulPasswordResetLinkRequestResponseContract
{
    public function toResponse($request)
    {
        return back()->with('success', 'Link has been sent to your email address. Please check your inbox and follow the instructions to reset your password.');
    }
}
