<?php

use App\Enums\RoleName;
use App\Models\School;
use App\Models\User;
use App\Support\CurrentSchool;
use Spatie\Permission\Models\Role;

test('root domain resolves to no school', function () {
    $this->get('http://'.config('app.domain').'/')->assertOk();

    expect(app(CurrentSchool::class)->getSchoolId())->toBeNull();
});

test('an unknown subdomain returns 404', function () {
    $this->get('http://unknown.'.config('app.domain').'/')->assertNotFound();
});

test('a known school subdomain resolves its school', function () {
    $domain = 'school1.'.config('app.domain');
    $school = School::factory()->create(['domain' => $domain]);

    $this->get("http://{$domain}/")->assertOk();

    expect(app(CurrentSchool::class)->getSchoolId())->toBe($school->id);
});

test('a non-admin user is forbidden from the admin domain', function () {
    $school = School::factory()->create();
    $user = User::factory()->for($school, 'school')->create();

    $this->actingAs($user)
        ->get('http://admin.'.config('app.domain').'/dashboard')
        ->assertForbidden();
});

test('an admin user with school_id null can access the admin domain', function () {
    Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    app(CurrentSchool::class)->setSchoolId(null);
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    $this->actingAs($admin)
        ->get('http://admin.'.config('app.domain').'/dashboard')
        ->assertOk();
});
