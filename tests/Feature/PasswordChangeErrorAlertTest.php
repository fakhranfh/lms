<?php

use App\Livewire\ChangePassword;
use App\Models\User;
use Livewire\Livewire;

test('wrong current password shows an inline error, not a redirect', function () {
    $user = User::factory()->create([
        'password' => bcrypt('OldPassword@123456'),
    ]);

    Livewire::actingAs($user)->test(ChangePassword::class)
        ->set('current_password', 'WrongPassword@123456')
        ->set('password', 'NewPassword@654321')
        ->set('password_confirmation', 'NewPassword@654321')
        ->call('updatePassword')
        ->assertHasErrors('current_password')
        ->assertSet('updated', false);
});

test('weak password shows a validation error', function () {
    $user = User::factory()->create([
        'password' => bcrypt('OldPassword@123456'),
    ]);

    Livewire::actingAs($user)->test(ChangePassword::class)
        ->set('current_password', 'OldPassword@123456')
        ->set('password', 'weak')
        ->set('password_confirmation', 'weak')
        ->call('updatePassword')
        ->assertHasErrors('password')
        ->assertSet('updated', false);
});

test('password confirmation mismatch shows a validation error', function () {
    $user = User::factory()->create([
        'password' => bcrypt('OldPassword@123456'),
    ]);

    Livewire::actingAs($user)->test(ChangePassword::class)
        ->set('current_password', 'OldPassword@123456')
        ->set('password', 'NewPassword@654321')
        ->set('password_confirmation', 'DifferentPassword@654321')
        ->call('updatePassword')
        ->assertHasErrors('password')
        ->assertSet('updated', false);
});
