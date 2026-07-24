<?php

use App\Http\Controllers\SchoolController;
use App\Services\PricingTierService;
use Illuminate\Support\Facades\Route;

// Root domain (lms.local): public landing + school registration.
Route::domain(config('app.domain'))->group(function () {
    Route::view('/', 'landing-page')->name('home');

    Route::view('/features', 'features')->name('features');

    Route::get('/pricing', function (PricingTierService $pricingTierService) {
        return view('pricing', [
            'tiers' => $pricingTierService->get(['is_active' => true], ['limits']),
        ]);
    })->name('pricing');

    Route::get('/register-school', function () {
        return view('schools.register');
    })->name('schools.register');

    Route::post('/register-school', [SchoolController::class, 'store'])->name('schools.store');
});
