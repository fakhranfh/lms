<?php

use App\Http\Controllers\PricingTierBreakdownController;
use App\Http\Controllers\TryDemoController;
use App\Livewire\MySchools;
use App\Livewire\SchoolAdminRegister;
use App\Livewire\SchoolRegister;
use App\Services\PricingTierService;
use App\Support\RootDomains;
use Illuminate\Support\Facades\Route;

// Root domain (lms.local) and any APP_EXTRA_DOMAINS: public landing + school registration.
// The primary domain (index 0) keeps the canonical route names used by route()
// calls throughout the app; extra domains get suffixed names (see RootDomains)
// so they can be served directly, without redirecting to the primary domain.
foreach (RootDomains::all() as $index => $rootDomain) {
    $suffix = $index === 0 ? '' : ".alt{$index}";

    Route::domain($rootDomain)->group(function () use ($suffix) {
        Route::view('/', 'landing-page')->name("home{$suffix}");

        Route::view('/features', 'features')->name("features{$suffix}");

        Route::get('/pricing', function (PricingTierService $pricingTierService) {
            return view('pricing', [
                'tiers' => $pricingTierService->get(['is_active' => true], ['limits']),
            ]);
        })->name("pricing{$suffix}");

        Route::get('/pricing-tiers/{pricingTier}/breakdown', [PricingTierBreakdownController::class, 'show'])->name("pricing-tiers.breakdown{$suffix}");

        Route::get('/get-started', SchoolAdminRegister::class)->name("get-started{$suffix}");
        Route::get('/get-started/school', SchoolRegister::class)->name("get-started.school{$suffix}");
        Route::get('/manage/schools', MySchools::class)->name("manage.schools.index{$suffix}");

        Route::get('/try-demo', [TryDemoController::class, 'index'])->name("try-demo{$suffix}");
        Route::get('/try-demo/{role}', [TryDemoController::class, 'login'])
            ->whereIn('role', ['instructor', 'student', 'school-admin'])
            ->name("try-demo.login{$suffix}");
    });
}
