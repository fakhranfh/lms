<?php

namespace App\Repositories\Auth;

interface AuthRepositoryInterface
{
    public function logout(): void;

    public function invalidateSession(): void;

    public function regenerateToken(): void;
}
