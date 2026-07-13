<?php

use App\Models\User;

test('landing page can be rendered', function () {
    $this->get('/')->assertSuccessful();
});

test('landing page can be accessed by guest users', function () {
    $this->assertGuest();

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewIs('landing-page');
});

test('landing page can be accessed by authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewIs('landing-page');
});

test('landing page returns correct view', function () {
    $response = $this->get('/');

    $response->assertViewIs('landing-page');
});
