<?php

use App\Models\User;

test('authenticated user can view change password page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/change-password');

    $response->assertStatus(200);
    $response->assertSee('Change Password');
    $response->assertSee('Current Password');
    $response->assertSee('New Password');
    $response->assertSee('Confirm Password');
});

test('change password page has password strength indicator', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/change-password');

    $response->assertStatus(200);
    $response->assertSee('passwordStrength');
    $response->assertSee('Password Security Tips');
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get('/change-password');

    $response->assertRedirectToRoute('login');
});

test('user can update password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('OldPassword@123456'),
    ]);

    $response = $this->actingAs($user)->put('/user/password', [
        'current_password' => 'OldPassword@123456',
        'password' => 'NewPassword@654321',
        'password_confirmation' => 'NewPassword@654321',
    ]);

    // Fortify redirects back with 'status' session key
    $response->assertStatus(302);

    // Verify status message is in session (Fortify uses 'status' key)
    $response->assertSessionHas('status');

    // Verify the password was actually changed
    $user->refresh();
    expect(password_verify('NewPassword@654321', $user->password))->toBeTrue();
});

test('change password button appears in edit profile page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/edit-profile');

    $response->assertStatus(200);
    $response->assertSee('/change-password');
    $response->assertSee('Change Password');
});

test('change password button appears in topbar dropdown', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    // The dropdown menu is in the page source
    $response->assertSee('Change Password', false);
});
