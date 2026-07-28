<?php

use App\Support\RootDomains;
use Illuminate\Support\Facades\Route;

// School subdomains (schoolN.lms.local) and any APP_EXTRA_DOMAINS: school-scoped app.
// The primary domain (index 0) keeps the canonical route name; extra domains
// get suffixed names (see RootDomains) so they can be served directly.
foreach (RootDomains::all() as $index => $rootDomain) {
    $suffix = $index === 0 ? '' : ".alt{$index}";

    Route::domain('{school}.'.$rootDomain)->group(function () use ($suffix) {
        Route::view('/', 'landing-page')->name("school.home{$suffix}");
    });
}
