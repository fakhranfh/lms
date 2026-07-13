<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function verifyEmailChange(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired verification link.');
        }

        /** @var User $user */
        $user = auth()->user();

        if ((int) $request->query('user') !== $user->id) {
            abort(403, 'This verification link does not belong to your account.');
        }

        if (! $user->pending_email) {
            return redirect()->route('edit-profile')->with('success', 'No pending email change found.');
        }

        $this->userService->confirmPendingEmail($user);

        return redirect()->route('edit-profile')->with('success', 'Email address updated successfully.');
    }
}
