<?php

use App\Http\Controllers\TryDemoController;
use App\Livewire\SchoolRegister;
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

    Route::get('/register-school', SchoolRegister::class)->name('schools.register');

    Route::get('/try-demo', [TryDemoController::class, 'index'])->name('try-demo');
    Route::get('/try-demo/{role}', [TryDemoController::class, 'login'])
        ->whereIn('role', ['instructor', 'student', 'school-admin'])
        ->name('try-demo.login');
});
