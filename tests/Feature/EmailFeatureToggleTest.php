<?php

use App\Livewire\EditProfile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

test('forgot password and email verification routes are registered by default', function () {
    $this->get('/forgot-password')->assertSuccessful();
    expect(Route::has('password.request'))->toBeTrue();
    expect(Route::has('verification.send'))->toBeTrue();
});

test('login page shows the forgot password link by default', function () {
    $this->get('/login')->assertSee('Forgot Password?');
});

describe('when FEATURE_EMAIL_ENABLED is false', function () {
    beforeEach(function () {
        putenv('FEATURE_EMAIL_ENABLED=false');
        $this->refreshApplication();
    });

    afterEach(function () {
        putenv('FEATURE_EMAIL_ENABLED=true');
    });

    test('forgot password route is not registered', function () {
        expect(Route::has('password.request'))->toBeFalse();

        $this->get('/forgot-password')->assertNotFound();
    });

    test('email verification routes are not registered', function () {
        expect(Route::has('verification.send'))->toBeFalse();

        $this->post('/email/verification-notification')->assertNotFound();
    });

    test('login page hides the forgot password link', function () {
        $this->get('/login')->assertDontSee('Forgot Password?');
    });

    test('unverified user is not blocked by the verified middleware', function () {
        $user = User::factory()->unverified()->create();

        expect($user->hasVerifiedEmail())->toBeTrue();

        $this->actingAs($user)->get('/dashboard')->assertSuccessful();
    });

    test('changing email updates it immediately without a pending verification step', function () {
        Mail::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)->test(EditProfile::class)
            ->set('name', $user->name)
            ->set('email', 'newemail@example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Profile updated successfully.')
            ->assertSet('pendingEmailSent', null);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'newemail@example.com',
            'pending_email' => null,
        ]);

        Mail::assertNothingSent();
    });
});
