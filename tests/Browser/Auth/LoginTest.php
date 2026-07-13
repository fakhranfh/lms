<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('login page can be rendered', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->assertSee('Email Address')
            ->assertSee('Password')
            ->assertSee('Login')
            ->assertSee("Don't have an account?")
            ->assertSee('Forgot Password?');
    });
});

test('user can login with valid credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', 'john@example.com')
            ->type('password', 'Password@123123')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->assertPathIs('/dashboard')
            ->assertSee('Dashboard');
    });
});

test('login fails with wrong password', function () {
    User::factory()->create([
        'email' => 'john@example.com',
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', 'john@example.com')
            ->type('password', 'wrong-password')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->assertPathIs('/login')
            ->assertSee('These credentials do not match our records.');
    });
});

test('login fails with unregistered email', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', 'nonexistent@example.com')
            ->type('password', 'Password@123123')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->assertPathIs('/login')
            ->assertSee('These credentials do not match our records.');
    });
});

test('login page has link to register page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->clickLink('Register')
            ->pause(500)
            ->assertPathIs('/register');
    });
});

test('login page has link to forgot password', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->clickLink('Forgot Password?')
            ->pause(500)
            ->assertPathIs('/forgot-password');
    });
});
