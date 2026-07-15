<?php

use App\Http\Controllers\SchoolController;
use Illuminate\Support\Facades\Route;

// Root domain (lms.local): public landing + school registration.
Route::domain(config('app.domain'))->group(function () {
    Route::view('/', 'landing-page')->name('home');

    Route::get('/register-school', function () {
        return view('schools.register');
    })->name('schools.register');

    Route::post('/register-school', [SchoolController::class, 'store'])->name('schools.store');
});
