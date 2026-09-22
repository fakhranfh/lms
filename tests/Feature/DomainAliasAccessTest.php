<?php

use App\Models\School;

test('root extra domain is served directly, without redirecting', function () {
    $this->get('http://lms.io/features')->assertOk();
});

test('school subdomain on extra domain redirects to login, without touching the primary domain', function () {
    School::factory()->create(['domain' => 'school1.lms.local']);

    $this->get('http://school1.lms.io/')
        ->assertRedirect('http://school1.lms.io/login');
});

test('canonical root domain still works', function () {
    $this->get('http://lms.local/features')->assertOk();
});

test('topbar links on an extra domain stay on that domain', function () {
    $response = $this->get('http://lms.io/');

    $response->assertOk();
    $response->assertSee('http://lms.io/get-started', false);
    $response->assertSee('http://lms.io/features', false);
    $response->assertDontSee('http://lms.local', false);
});

test('topbar links on the primary domain stay on the primary domain', function () {
    $response = $this->get('http://lms.local/');

    $response->assertOk();
    $response->assertSee('http://lms.local/get-started', false);
});
