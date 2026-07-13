<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('authenticated user sees logout button and can logout', function () {
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
            ->click('@user-menu-toggle')
            ->pause(300)
            ->assertSee('Logout')
            ->press('Logout')
            ->pause(1000)
            ->assertPathIs('/login');
    });
});

test('after logout user cannot access protected pages', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitForLocation('/login')
            ->type('email', $user->email)
            ->type('password', 'Password@123123')
            ->pause(500)
            ->press('Login')
            ->pause(1000)
            ->assertSee('Dashboard')
            ->click('@user-menu-toggle')
            ->pause(300)
            ->press('Logout')
            ->pause(1000)
            ->assertPathIs('/login')
            ->assertSee('Email Address');
    });
});

test('guest browsing dashboard is redirected to login', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/dashboard')
            ->pause(500)
            ->assertPathIs('/login');
    });
});
