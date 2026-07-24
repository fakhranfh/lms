<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('super admin can view pulse', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    expect(Gate::forUser($admin)->allows('viewPulse'))->toBeTrue();
});

test('non-admin cannot view pulse', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewPulse'))->toBeFalse();
});
