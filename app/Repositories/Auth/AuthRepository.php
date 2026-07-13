<?php

namespace App\Repositories\Auth;

use Illuminate\Http\Request;

class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(private Request $request) {}

    public function logout(): void
    {
        auth()->logout();
    }

    public function invalidateSession(): void
    {
        $this->request->session()->invalidate();
    }

    public function regenerateToken(): void
    {
        $this->request->session()->regenerateToken();
    }
}
