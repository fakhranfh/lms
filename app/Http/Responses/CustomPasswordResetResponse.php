<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;

class CustomPasswordResetResponse implements PasswordResetResponseContract
{
    public function toResponse($request)
    {
        return redirect(route('login'))
            ->with('status', 'Password reset successful. You can now log in with your new password.');
    }
}