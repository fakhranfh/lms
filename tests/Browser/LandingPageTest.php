<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('landing page can be rendered', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Solid Foundation for Your App');
    });
});

test('landing page displays correct heading and description', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Solid Foundation for Your App')
            ->assertSee('Stop wasting time on complicated setups. Build your project on a clean codebase and focus on what actually matters.');
    });
});

test('guest users see login and register links', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Log in')
            ->assertSee('Register');
    });
});

test('guest users can navigate to login from landing page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->clickLink('Log in')
            ->assertPathIs('/login');
    });
});

test('guest users can navigate to register from landing page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->clickLink('Get Started')
            ->assertPathIs('/register');
    });
});

test('authenticated users see dashboard link', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/')
            ->assertSee('Dashboard')
            ->assertDontSee('Log in');
    });
});

test('authenticated users can navigate to dashboard from landing page', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/')
            ->clickLink('Go to Dashboard')
            ->assertPathIs('/dashboard');
    });
});

test('landing page has app name in navbar', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee(config('app.name', 'Laravel'));
    });
});
