<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\VerifyEmailViewResponse as VerifyEmailViewResponseContract;

class CustomVerifyEmailViewResponse implements VerifyEmailViewResponseContract
{
    public function toResponse($request)
    {
        return view('auth.verify-email');
    }
}