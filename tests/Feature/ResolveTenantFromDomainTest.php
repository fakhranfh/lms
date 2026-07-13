<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Spatie\Permission\Models\Role;

test('root domain resolves to no tenant', function () {
    $this->get('http://'.config('app.domain').'/')->assertOk();

    expect(app(CurrentTenant::class)->getTenantId())->toBeNull();
});

test('an unknown subdomain returns 404', function () {
    $this->get('http://unknown.'.config('app.domain').'/')->assertNotFound();
});

test('a known school subdomain resolves its tenant', function () {
    $domain = 'school1.'.config('app.domain');
    $tenant = Tenant::factory()->create(['domain' => $domain]);

    $this->get("http://{$domain}/")->assertOk();

    expect(app(CurrentTenant::class)->getTenantId())->toBe($tenant->id);
});

test('a non-admin user is forbidden from the admin domain', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->for($tenant, 'tenant')->create();

    $this->actingAs($user)
        ->get('http://admin.'.config('app.domain').'/admin/dashboard')
        ->assertForbidden();
});

test('an admin user with tenant_id null can access the admin domain', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    app(CurrentTenant::class)->setTenantId(null);
    $admin = User::factory()->create(['tenant_id' => null]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('http://admin.'.config('app.domain').'/admin/dashboard')
        ->assertOk();
});
