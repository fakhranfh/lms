<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    Notification::fake();
});

test('forgot password page can be rendered', function () {
    $this->get('/forgot-password')->assertSuccessful();
});

test('password reset link can be requested with valid email', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset link fails with unregistered email', function () {
    $this->post('/forgot-password', ['email' => 'nonexistent@example.com'])
        ->assertSessionHasErrors('email');
});

test('reset password page loads with valid token', function () {
    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);

    $this->get("/reset-password/{$token}")
        ->assertSuccessful();
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewSecret!Pass123#Secure',
        'password_confirmation' => 'NewSecret!Pass123#Secure',
    ])
        ->assertRedirect('/login')
        ->assertSessionHas('status');

    $this->assertTrue(Hash::check('NewSecret!Pass123#Secure', $user->fresh()->password));
});

test('password reset fails with weak password', function () {
    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');
});
