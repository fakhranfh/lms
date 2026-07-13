<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('password change displays success alert', function () {
    User::factory()->create([
        'email' => 'dusk-pwd@example.com',
        'password' => bcrypt('Password123'),
        'email_verified_at' => now(),
    ]);

    $this->browse(function (Browser $browser) {
        $browser
            // Login
            ->visit('/login')
            ->type('email', 'dusk-pwd@example.com')
            ->type('password', 'Password123')
            ->press('Login')
            ->pause(3000)
            ->screenshot('01-after-login-attempt')

            // Navigate directly to change password (user should be authenticated)
            ->visit('/change-password')
            ->pause(2000)
            ->screenshot('02-change-password-page')
            ->assertSee('Change Password');

        // Try to fill and submit form
        try {
            $browser
                ->type('current_password', 'Password123')
                ->type('password', 'Zx9#mK$vP2@nL8qR!')
                ->type('password_confirmation', 'Zx9#mK$vP2@nL8qR!')
                ->screenshot('03-form-filled')
                ->press('Update Password')
                ->pause(3000)
                ->screenshot('04-after-submit');

            // Check success message appears
            $browser->waitForText('Your password has been changed successfully', 10)
                ->screenshot('05-success-message-visible');
        } catch (Exception $e) {
            // Fallback: Just verify we can access the page
            $browser->screenshot('error-'.now()->timestamp);
            throw $e;
        }
    });
});
