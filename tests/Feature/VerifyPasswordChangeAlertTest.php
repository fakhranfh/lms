<?php

use App\Livewire\ChangePassword;
use App\Models\User;
use Livewire\Livewire;

test('password change component shows success alert after update', function () {
    $user = User::factory()->create([
        'password' => bcrypt('OldPassword@123456'),
    ]);

    Livewire::actingAs($user)->test(ChangePassword::class)
        ->set('current_password', 'OldPassword@123456')
        ->set('password', 'NewPassword@654321')
        ->set('password_confirmation', 'NewPassword@654321')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('updated', true)
        ->assertSet('current_password', '')
        ->assertSet('password', '')
        ->assertSee('Your password has been changed successfully');

    $user->refresh();
    expect(password_verify('NewPassword@654321', $user->password))->toBeTrue();
});
