<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('super admin can view horizon', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeTrue();
});

test('non-admin cannot view horizon', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBeFalse();
});

test('unauthenticated visitor cannot view horizon', function () {
    expect(Gate::forUser(null)->allows('viewHorizon'))->toBeFalse();
});
