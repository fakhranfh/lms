<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('edit profile page can be rendered', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/edit-profile')
            ->assertSee('Edit Profile');
    });
});

test('user can update name successfully', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/edit-profile')
            ->clear('name')
            ->type('name', 'New Name')
            ->press('Save Changes')
            ->pause(1000)
            ->assertSee('Profile updated successfully.');
    });

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
});

test('update profile fails when name is empty', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/edit-profile')
            ->clear('name')
            ->script("document.getElementById('name').removeAttribute('required')");

        $browser->press('Save Changes')
            ->pause(1000)
            ->assertSee('Full name is required');
    });
});

test('changing email triggers pending verification notice', function () {
    $user = User::factory()->create(['email' => 'original@example.com']);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/edit-profile')
            ->clear('email')
            ->type('email', 'newemail@example.com')
            ->press('Save Changes')
            ->pause(1000)
            ->assertSee('newemail@example.com');
    });
});

test('update profile fails when email format is invalid', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/edit-profile')
            ->script("
                var el = document.getElementById('email');
                el.removeAttribute('type');
                el.value = 'not-an-email';
            ");

        $browser->press('Save Changes')
            ->pause(1000)
            ->assertSee('Please enter a valid email address');
    });
});
