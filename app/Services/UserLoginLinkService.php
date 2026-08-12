<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginLink;
use App\Repositories\UserLoginLink\UserLoginLinkRepositoryInterface;
use Illuminate\Support\Str;

class UserLoginLinkService
{
    public function __construct(
        protected UserLoginLinkRepositoryInterface $userLoginLinkRepository,
    ) {}

    /**
     * Generate a unique login link token.
     */
    public function generateToken(): string
    {
        $token = Str::random(48);

        while ($this->userLoginLinkRepository->tokenExists($token)) {
            $token = Str::random(48);
        }

        return $token;
    }

    /**
     * Create a single-use login link for the given user, valid for the
     * given number of minutes.
     */
    public function createLink(User $user, int $ttlMinutes = 15): UserLoginLink
    {
        return $this->userLoginLinkRepository->create([
            'user_id' => $user->id,
            'token' => $this->generateToken(),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    /**
     * Find a still-valid, unused login link by its token.
     */
    public function findValidByToken(string $token): ?UserLoginLink
    {
        return $this->userLoginLinkRepository->findValidByToken($token);
    }

    /**
     * Build the absolute login URL for a link, scoped to the user's school
     * domain so multi-tenant session/auth state resolves correctly.
     */
    public function buildLoginUrl(UserLoginLink $link, string $scheme = 'http', ?int $port = null): string
    {
        $school = $link->user->school();
        $domain = $school !== null ? $school->domain : parse_url((string) config('app.url'), PHP_URL_HOST);

        $url = "{$scheme}://{$domain}/login-link/{$link->token}";

        if ($port && ! in_array($port, [80, 443], true)) {
            $url = "{$scheme}://{$domain}:{$port}/login-link/{$link->token}";
        }

        return $url;
    }
}
