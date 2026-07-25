<?php

use App\Livewire\SchoolAdminRegister;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

test('creates a school-less admin account and logs them in', function () {
    Livewire::test(SchoolAdminRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'Xk9$vQz2!pLw7Rf')
        ->set('password_confirmation', 'Xk9$vQz2!pLw7Rf')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('get-started.school'));

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    expect($user->school_id)->toBeNull()
        ->and(Auth::id())->toBe($user->id);
});

test('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    Livewire::test(SchoolAdminRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'Xk9$vQz2!pLw7Rf')
        ->set('password_confirmation', 'Xk9$vQz2!pLw7Rf')
        ->call('save')
        ->assertHasErrors('email');
});

test('rejects a mismatched password confirmation', function () {
    Livewire::test(SchoolAdminRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'Xk9$vQz2!pLw7Rf')
        ->set('password_confirmation', 'Yn4$mBc8!qTs3Vd')
        ->call('save')
        ->assertHasErrors('password');
});
