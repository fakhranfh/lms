<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\IpGeolocationService;
use App\Support\CurrentTenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private IpGeolocationService $ipGeolocationService,
        private CurrentTenant $currentTenant,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $tenantId = $this->currentTenant->getTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'email' => 'Unable to determine which school to register for. Please register from your school\'s subdomain.',
            ]);
        }

        $timezone = $this->ipGeolocationService->detectTimezone(request()->ip()) ?? config('app.timezone');

        return User::create([
            'tenant_id' => $tenantId,
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'timezone' => $timezone,
        ]);
    }
}
