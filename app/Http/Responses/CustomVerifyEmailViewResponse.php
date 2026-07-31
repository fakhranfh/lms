<?php

namespace App\Http\Responses;

use Illuminate\Http\Response;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse as VerifyEmailViewResponseContract;

class CustomVerifyEmailViewResponse implements VerifyEmailViewResponseContract
{
    public function toResponse($request): Response
    {
        return response()->view('auth.verify-email');
    }
}
