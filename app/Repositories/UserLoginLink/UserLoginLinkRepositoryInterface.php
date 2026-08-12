<?php

namespace App\Repositories\UserLoginLink;

use App\Models\UserLoginLink;

interface UserLoginLinkRepositoryInterface
{
    /**
     * Determine whether the given token is already in use.
     */
    public function tokenExists(string $token): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UserLoginLink;

    /**
     * Find an unused, unexpired login link by its token.
     */
    public function findValidByToken(string $token): ?UserLoginLink;
}
