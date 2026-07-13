<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('unverified user sees email verification notice after login', function () {
    $user = User::factory()->unverified()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', $user->email)
            ->type('password', 'Password@123123')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->assertPathIs('/email/verify')
            ->assertSee('Verify your email address');
    });
});

test('verified user goes to dashboard after login', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', $user->email)
            ->type('password', 'Password@123123')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->assertPathIs('/dashboard')
            ->assertSee('Dashboard');
    });
});

test('resend verification email button shows success message', function () {
    $user = User::factory()->unverified()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', $user->email)
            ->type('password', 'Password@123123')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->press('Resend Verification Email')
            ->pause(1000)
            ->assertPathIs('/email/verify')
            ->assertSee('A new verification link has been sent');
    });
});

test('logout button on verify page works', function () {
    $user = User::factory()->unverified()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', $user->email)
            ->type('password', 'Password@123123')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->assertPathIs('/email/verify')
            ->press('Log out')
            ->pause(1000)
            ->assertPathIs('/login');
    });
});
