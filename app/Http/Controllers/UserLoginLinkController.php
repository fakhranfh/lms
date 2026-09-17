<?php

namespace App\Http\Controllers;

use App\Services\UserLoginLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UserLoginLinkController extends Controller
{
    public function __construct(private UserLoginLinkService $userLoginLinkService) {}

    /**
     * Consume a single-use login link and sign the linked user in.
     */
    public function login(string $token): RedirectResponse
    {
        $link = $this->userLoginLinkService->findValidByToken($token);

        if (! $link) {
            return redirect()
                ->route('login')
                ->with('error', 'Invalid or expired login link.');
        }

        Auth::login($link->user);

        $link->update(['used_at' => now()]);

        if ($link->user->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->route('dashboard');
    }
}
