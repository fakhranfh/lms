<?php

use App\Models\User;

test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});

test('guest cannot access logout', function () {
    $this->post('/logout')
        ->assertRedirect('/login');
});

test('session is invalidated after logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->post('/logout');

    $this->assertGuest();
    $this->assertNull(auth()->user());
});
