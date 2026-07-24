<?php

use App\Models\DemoLmsAccess;
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

test('demo user is redirected to the try demo page after logout', function () {
    $access = DemoLmsAccess::factory()->create(['expires_at' => now()->addDays(14)]);

    $this->actingAs($access->user)
        ->post('/logout')
        ->assertRedirect(route('try-demo'));

    $this->assertGuest();
});
