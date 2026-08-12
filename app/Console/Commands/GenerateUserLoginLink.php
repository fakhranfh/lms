<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserLoginLinkService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('user:login-link {user : User ID, email, or exact name} {--ttl=15 : Minutes the link stays valid}')]
#[Description('Generate a single-use login link for a user')]
class GenerateUserLoginLink extends Command
{
    public function handle(UserLoginLinkService $userLoginLinkService): int
    {
        $identifier = $this->argument('user');
        $ttl = (int) $this->option('ttl');

        $user = $this->resolveUser($identifier);

        if ($user === null) {
            $this->error("No user found matching \"{$identifier}\".");

            return 1;
        }

        $link = $userLoginLinkService->createLink($user, $ttl);
        $url = $userLoginLinkService->buildLoginUrl($link, request()->getScheme());

        $this->info("Login link for {$user->name} ({$user->email}):");
        $this->line($url);
        $this->comment("Expires in {$ttl} minute(s), single use.");

        return 0;
    }

    private function resolveUser(string $identifier): ?User
    {
        if (Str::isUuid($identifier) && ($user = User::find($identifier)) !== null) {
            return $user;
        }

        if (($user = User::where('email', $identifier)->first()) !== null) {
            return $user;
        }

        $matches = User::where('name', $identifier)->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            $this->error("Multiple users named \"{$identifier}\" found. Use their email or ID instead:");
            foreach ($matches as $match) {
                $this->line("  {$match->id}  {$match->email}");
            }
        }

        return null;
    }
}
