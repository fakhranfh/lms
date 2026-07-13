<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Dusk\Browser;

beforeEach(function () {
    Browser::$storeScreenshotsAt = base_path('docs/dusk/images');
});

/**
 * @group screenshots
 */
test('capture landing page screenshot', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->pause(500)
            ->screenshot('landing-page');
    });
});

test('capture register page screenshot', function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->pause(500)
            ->screenshot('register-page');
    });
});

test('capture register error screenshot', function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

    User::factory()->create(['email' => 'taken@example.com']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'taken@example.com')
            ->type('password', 'Secret!Pass123#Secure')
            ->type('password_confirmation', 'Secret!Pass123#Secure')
            ->press('Register')
            ->pause(1000)
            ->screenshot('register-error');
    });
});

test('capture login page screenshot', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->pause(500)
            ->screenshot('login-page');
    });
});

test('capture login error screenshot', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->type('email', 'john@example.com')
            ->type('password', 'wrong-password')
            ->script("document.getElementById('loginButton').click()");

        $browser->pause(1500)
            ->screenshot('login-error');
    });
});

test('capture email verify notice screenshot', function () {
    $user = User::factory()->unverified()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->type('email', $user->email)
            ->type('password', 'Password@123123')
            ->script("document.getElementById('loginButton').click()");

        $browser->pause(2000)
            ->screenshot('email-verify-notice');
    });
});

test('capture forgot password page screenshot', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/forgot-password')
            ->pause(500)
            ->screenshot('forgot-password-page');
    });
});

test('capture reset password page screenshot', function () {
    Notification::fake();

    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);

    $this->browse(function (Browser $browser) use ($token) {
        $browser->visit("/reset-password/{$token}")
            ->waitFor("button[type='submit']")
            ->screenshot('reset-password-page');
    });
});

test('capture dashboard screenshot', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/dashboard')
            ->pause(500)
            ->screenshot('dashboard');
    });
});

test('capture edit profile page screenshot', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/edit-profile')
            ->pause(500)
            ->screenshot('edit-profile-page');
    });
});

test('capture edit profile success screenshot', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/edit-profile')
            ->clear('name')
            ->type('name', 'New Name')
            ->press('Save Changes')
            ->pause(1500)
            ->screenshot('edit-profile-success');
    });
});

test('capture change password page screenshot', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Password@123123'),
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/change-password')
            ->pause(500)
            ->screenshot('change-password-page');
    });
});

test('capture change password success screenshot', function () {
    $user = User::factory()->create([
        'email' => 'dusk-pwd-doc@example.com',
        'password' => bcrypt('Password@123123'),
        'email_verified_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/change-password')
            ->pause(500)
            ->type('current_password', 'Password@123123')
            ->type('password', 'NewPassword@12345')
            ->type('password_confirmation', 'NewPassword@12345')
            ->press('Update Password')
            ->pause(3000)
            ->screenshot('change-password-success');
    });
});
