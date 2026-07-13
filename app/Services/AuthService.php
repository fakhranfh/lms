<?php

namespace App\Services;

use App\Repositories\Auth\AuthRepositoryInterface;

class AuthService
{
    public function __construct(private AuthRepositoryInterface $authRepository) {}

    public function logout(): void
    {
        $this->authRepository->logout();
        $this->authRepository->invalidateSession();
        $this->authRepository->regenerateToken();
    }
}
