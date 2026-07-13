<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.required' => __('Current password is required.'),
            'current_password.current_password' => __('Your current password is incorrect. Please try again.'),
            'password.required' => __('New password is required.'),
            'password.min' => __('Password must be at least 8 characters long.'),
            'password.regex' => __('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'),
            'password.confirmed' => __('Password confirmation does not match.'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
