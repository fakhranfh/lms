<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Dusk\Browser;

beforeEach(function () {
    Notification::fake();
});

test('forgot password page can be rendered', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/forgot-password')
            ->waitForLocation('/forgot-password')
            ->assertSee('Forgot Password')
            ->assertSee('Send Instructions')
            ->assertSee('Back to log in');
    });
});

test('reset link can be requested with valid email', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/forgot-password')
            ->waitForLocation('/forgot-password')
            ->type('email', $user->email)
            ->pause(500)
            ->press('Send Instructions')
            ->pause(1000)
            ->assertPathIs('/forgot-password')
            ->assertSee('Link has been sent to your email address');
    });
});

test('reset link fails with unregistered email', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/forgot-password')
            ->waitForLocation('/forgot-password')
            ->type('email', 'nonexistent@example.com')
            ->pause(500)
            ->press('Send Instructions')
            ->pause(1000)
            ->assertPathIs('/forgot-password')
            ->assertSee("We can't find a user with that email address");
    });
});

test('reset password page loads with valid token', function () {
    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);

    $this->browse(function (Browser $browser) use ($token) {
        $browser->visit("/reset-password/{$token}")
            ->waitFor("button[type='submit']")
            ->assertSee('Reset your password')
            ->assertSee('New Password')
            ->assertSee('Confirm New Password');
    });
});

test('reset password shows js error with weak password', function () {
    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);

    $this->browse(function (Browser $browser) use ($user, $token) {
        $browser->visit("/reset-password/{$token}?email={$user->email}")
            ->waitFor("button[type='submit']")
            ->type('password', 'short')
            ->type('password_confirmation', 'short')
            ->pause(500)
            ->press('Reset Password')
            ->pause(1000)
            ->assertPathIs("/reset-password/{$token}")
            ->assertSeeIn('#password-js-error', 'at least 8 characters');
    });
});

test('forgot password page has back to login link', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/forgot-password')
            ->waitForLocation('/forgot-password')
            ->clickLink('Back to log in')
            ->pause(500)
            ->assertPathIs('/login');
    });
});
