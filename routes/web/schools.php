<?php

use Illuminate\Support\Facades\Route;

// School subdomains (schoolN.lms.local): school-scoped app.
Route::domain('{school}.'.config('app.domain'))->group(function () {
    Route::view('/', 'landing-page')->name('school.home');
});
