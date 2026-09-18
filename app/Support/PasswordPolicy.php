<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Single source of truth for the password complexity requirement used by
 * every feature that sets or changes a password (registration, password
 * reset, force-password-change, and the user/student admin forms). Keep
 * this in sync with resources/js/password-policy.js, which mirrors the
 * same rule for client-side validation.
 */
class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    public static function complexityRule(): Password
    {
        return Password::min(self::MIN_LENGTH)->letters()->numbers()->symbols();
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $required = true, bool $confirmed = true): array
    {
        return array_filter([
            $required ? 'required' : 'nullable',
            'string',
            self::complexityRule(),
            $confirmed ? 'confirmed' : null,
        ]);
    }
}
