<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('login page can be rendered', function () {
    $this->get('/login')->assertSuccessful();
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

test('login fails with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login fails with unregistered email', function () {
    $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login updates timezone from ip address', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('Asia/Jakarta', 200),
    ]);

    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
        'timezone' => 'UTC',
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);
});

test('login keeps existing timezone when ip lookup fails', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('', 500),
        'ip-api.com/*' => Http::response('', 500),
    ]);

    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
        'timezone' => 'Asia/Jakarta',
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);
});

test('login is throttled after 5 failed attempts', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ])->assertStatus(429);
});
