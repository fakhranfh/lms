<?php

namespace App\Services;

use App\Models\DemoLmsAccess;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;

class DemoLmsAccessService
{
    /**
     * Generate a unique access token for demo LMS.
     */
    public function generateAccessToken(School $school): string
    {
        $token = Str::random(32);

        while (DemoLmsAccess::where('access_token', $token)->exists()) {
            $token = Str::random(32);
        }

        return $token;
    }

    /**
     * Create a demo admin account for the school.
     */
    public function createDemoUser(School $school): User
    {
        $email = 'demo-'.$school->id.'@demo.'.$school->domain;

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Demo Admin - '.$school->name,
                'school_id' => $school->id,
                'password' => bcrypt('demo-password'),
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Grant demo access to a user for 14 days.
     */
    public function grantDemoAccess(School $school, User $user): DemoLmsAccess
    {
        $token = $this->generateAccessToken($school);
        $expiresAt = now()->addDays(14);

        return DemoLmsAccess::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'access_token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check if demo access is still valid.
     */
    public function isDemoAccessValid(DemoLmsAccess $access): bool
    {
        if ($access->expires_at === null) {
            return false;
        }

        return $access->expires_at->isFuture();
    }

    /**
     * Get or create demo access for a school.
     */
    public function getOrCreateDemoAccess(School $school): DemoLmsAccess
    {
        $validAccess = DemoLmsAccess::where('school_id', $school->id)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if ($validAccess) {
            return $validAccess;
        }

        $user = $this->createDemoUser($school);

        return $this->grantDemoAccess($school, $user);
    }

    /**
     * Regenerate demo access for a school, always creating a new token.
     */
    public function regenerateDemoAccess(School $school): DemoLmsAccess
    {
        $user = $this->createDemoUser($school);

        return $this->grantDemoAccess($school, $user);
    }
}
