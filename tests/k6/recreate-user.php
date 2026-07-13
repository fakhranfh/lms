<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::where('email', 'loadtest@example.com')->delete();

User::factory()->create([
    'email' => 'loadtest@example.com',
    'password' => Hash::make('password'),
]);

echo "User recreated: loadtest@example.com / password\n";
