<?php

namespace App\Http\Responses;

use App\Repositories\PaymentTransaction\PaymentTransactionRepositoryInterface;
use Laravel\Fortify\Contracts\LoginResponse;

class CustomAuthenticatedSessionResponse implements LoginResponse
{
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user && ! $request->session()->has('url.intended')) {
            $pendingTransaction = app(PaymentTransactionRepositoryInterface::class)
                ->findPendingRegistrationForUser($user->id);

            if ($pendingTransaction) {
                return redirect()->route('school.payment.index', $pendingTransaction);
            }
        }

        return redirect()->intended(config('fortify.home'));
    }
}
