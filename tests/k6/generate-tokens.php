<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;

$user = User::where('email', 'loadtest@example.com')->first();

if (! $user) {
    $user = User::factory()->create([
        'email' => 'loadtest@example.com',
        'password' => bcrypt('password'),
    ]);
}

$tokens = [];
for ($i = 0; $i < 30; $i++) {
    $token = Password::getRepository()->create($user);
    $tokens[] = ['token' => $token, 'email' => $user->email];
}

file_put_contents(
    __DIR__ . '/tokens.json',
    json_encode($tokens, JSON_PRETTY_PRINT)
);

echo "Created " . count($tokens) . " tokens in tokens.json\n";
