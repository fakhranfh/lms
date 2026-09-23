<?php

use App\Http\Controllers\TryDemoController;
use Illuminate\Support\Facades\Route;

// Public landing. Not scoped to any particular host, so it's reachable at
// whatever domain (or IP) the app is served from.
Route::view('/', 'landing-page')->name('home');

Route::view('/features', 'features')->name('features');

Route::get('/try-demo', [TryDemoController::class, 'index'])->name('try-demo');
Route::get('/try-demo/{role}', [TryDemoController::class, 'login'])
    ->whereIn('role', ['teacher', 'student', 'school-admin'])
    ->name('try-demo.login');
