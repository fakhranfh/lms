<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::factory()->create([
    'email' => 'loadtest@example.com',
    'password' => Hash::make('password'),
]);

echo "User created: loadtest@example.com\n";
