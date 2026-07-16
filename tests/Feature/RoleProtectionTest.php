<?php

use App\Models\Role;

test('protected admin role cannot be deleted', function () {
    $adminRole = Role::where('name', 'Admin')->first();
    expect($adminRole)->not->toBeNull();
    expect($adminRole->protected)->toBeTrue();

    expect(function () use ($adminRole) {
        $adminRole->delete();
    })->toThrow(Exception::class, 'The Admin role cannot be deleted.');

    expect(Role::where('name', 'Admin')->exists())->toBeTrue();
});

test('protected role cannot be force deleted', function () {
    $adminRole = Role::where('name', 'Admin')->first();

    expect(function () use ($adminRole) {
        $adminRole->forceDelete();
    })->toThrow(Exception::class, 'The Admin role cannot be deleted.');

    expect(Role::where('name', 'Admin')->exists())->toBeTrue();
});

test('unprotected roles can be deleted', function () {
    $role = Role::create([
        'name' => 'TestRole',
        'guard_name' => 'web',
        'protected' => false,
    ]);

    expect($role->delete())->toBe(true);
    expect(Role::where('name', 'TestRole')->exists())->toBeFalse();
});
