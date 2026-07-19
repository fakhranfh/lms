<?php

use App\Models\User;

test('admin login page can be rendered', function () {
    $this->get(route('admin.login'))
        ->assertSuccessful()
        ->assertViewIs('auth.admin-login');
});

test('admin user can login on admin domain', function () {
    $user = User::factory()->create([
        'email' => 'admin-'.time().'@example.com',
        'password' => 'password',
        'school_id' => null,
    ]);
    $user->assignRole('Admin');

    $response = $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticated();
    $this->assertTrue(auth()->check() && auth()->user()->hasRole('Admin'));
});

test('non-admin user cannot login on admin domain', function () {
    $user = User::factory()->create([
        'email' => 'user-'.time().'@example.com',
        'password' => 'password',
    ]);

    $response = $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(302);
    $this->assertGuest();
});

test('admin login fails with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'admin-wrong-'.time().'@example.com',
        'password' => 'password',
        'school_id' => null,
    ]);
    $user->assignRole('Admin');

    $response = $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(302);
    $this->assertGuest();
});

test('admin can access admin dashboard', function () {
    $user = User::factory()->create([
        'school_id' => null,
    ]);
    $user->assignRole('Admin');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful();
});

test('authenticated admin can logout from admin domain', function () {
    $user = User::factory()->create([
        'school_id' => null,
    ]);
    $user->assignRole('Admin');

    $response = $this->actingAs($user)
        ->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest();
});
