<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Dusk\Browser;

beforeEach(function () {
    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
    ]);
});

test('registration page can be rendered', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->assertSee('Create Account')
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Password')
            ->assertSee('Confirm Password')
            ->assertSee('Register')
            ->assertSee('Already have an account?');
    });
});

test('user can register with valid data', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'Secret!Pass123#Secure')
            ->type('password_confirmation', 'Secret!Pass123#Secure')
            ->press('Register')
            ->assertPathIs('/email/verify')
            ->assertSee('Verify your email address');
    });

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('registration fails when name is empty', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('email', 'john@example.com')
            ->type('password', 'Secret!Pass123#Secure')
            ->type('password_confirmation', 'Secret!Pass123#Secure');

        $browser->script("document.getElementById('name').removeAttribute('required')");

        $browser->press('Register')
            ->assertPathIs('/register')
            ->assertSee('The name field is required.');
    });
});

test('registration fails when email is empty', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('password', 'Secret!Pass123#Secure')
            ->type('password_confirmation', 'Secret!Pass123#Secure');

        $browser->script("document.getElementById('email').removeAttribute('required')");

        $browser->press('Register')
            ->assertPathIs('/register')
            ->assertSee('The email field is required.');
    });
});

test('registration fails when email format is invalid', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'not-an-email')
            ->type('password', 'Secret!Pass123#Secure')
            ->type('password_confirmation', 'Secret!Pass123#Secure');

        $browser->script("document.getElementById('email').removeAttribute('type')");

        $browser->press('Register')
            ->assertPathIs('/register')
            ->assertSee('The email field must be a valid email address.');
    });
});

test('registration fails when email is already taken', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'Secret!Pass123#Secure')
            ->type('password_confirmation', 'Secret!Pass123#Secure')
            ->press('Register')
            ->assertPathIs('/register')
            ->assertSee('The email has already been taken.');
    });
});

test('registration fails when password is less than 8 characters', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'Sh0rt!')
            ->type('password_confirmation', 'Sh0rt!')
            ->press('Register')
            ->assertPathIs('/register')
            ->assertSeeIn('#password-js-error', 'Password must be at least 8 characters long.');
    });
});

test('registration shows js error when password has no mixed case', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'password123#!')
            ->type('password_confirmation', 'password123#!')
            ->press('Register')
            ->assertPathIs('/register')
            ->assertSeeIn('#password-js-error', 'both lowercase and uppercase');
    });
});

test('registration shows js error when password has no numbers', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'Password#!Secure')
            ->type('password_confirmation', 'Password#!Secure')
            ->press('Register')
            ->assertPathIs('/register')
            ->assertSeeIn('#password-js-error', 'at least one number');
    });
});

test('registration shows js error when password has no symbols', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'Password123Secure')
            ->type('password_confirmation', 'Password123Secure')
            ->press('Register')
            ->assertPathIs('/register')
            ->assertSeeIn('#password-js-error', 'at least one symbol');
    });
});

test('registration shows js error when password confirmation does not match', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'Secret!Pass123#Secure')
            ->type('password_confirmation', 'Different!Pass123#Secure')
            ->press('Register')
            ->assertPathIs('/register')
            ->assertSeeIn('#confirm-password-js-error', 'Password must be the same as the confirmation.');
    });
});

test('registration page has login link', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->clickLink('Login here')
            ->assertPathIs('/login');
    });
});
