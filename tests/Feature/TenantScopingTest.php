<?php

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;

test('a query run under tenant A never returns rows belonging to tenant B', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    User::factory()->for($tenantA, 'tenant')->create();
    User::factory()->for($tenantB, 'tenant')->create();

    app(CurrentTenant::class)->setTenantId($tenantA->id);

    $users = User::all();

    expect($users)->toHaveCount(1);
    expect($users->first()->tenant_id)->toBe($tenantA->id);
});

test('creating a record without tenant_id assigns the current tenant automatically', function () {
    $tenant = Tenant::factory()->create();

    app(CurrentTenant::class)->setTenantId($tenant->id);

    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    expect($user->tenant_id)->toBe($tenant->id);
});

test('withoutGlobalScope bypasses tenant scoping for cross-tenant console operations', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->for($tenantA, 'tenant')->create();
    $userB = User::factory()->for($tenantB, 'tenant')->create();

    app(CurrentTenant::class)->setTenantId($tenantA->id);

    $allUserIds = User::withoutGlobalScope(TenantScope::class)->pluck('id');

    expect($allUserIds)->toContain($userA->id, $userB->id);
});

test('no tenant context means queries are unscoped', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->for($tenantA, 'tenant')->create();
    $userB = User::factory()->for($tenantB, 'tenant')->create();

    app(CurrentTenant::class)->setTenantId(null);

    $userIds = User::all()->pluck('id');

    expect($userIds)->toContain($userA->id, $userB->id);
});
