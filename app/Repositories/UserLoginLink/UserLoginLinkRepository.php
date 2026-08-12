<?php

namespace App\Repositories\UserLoginLink;

use App\Models\UserLoginLink;

class UserLoginLinkRepository implements UserLoginLinkRepositoryInterface
{
    public function tokenExists(string $token): bool
    {
        return UserLoginLink::where('token', $token)->exists();
    }

    public function create(array $data): UserLoginLink
    {
        return UserLoginLink::create($data);
    }

    public function findValidByToken(string $token): ?UserLoginLink
    {
        return UserLoginLink::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
