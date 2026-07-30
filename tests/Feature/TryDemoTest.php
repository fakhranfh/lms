<?php

use App\Models\DemoLmsAccess;
use App\Models\School;
use Database\Seeders\PricingTierSeeder;

test('the try demo page is reachable on the root domain', function () {
    $rootDomain = config('app.domain');

    $response = $this->get("http://{$rootDomain}/try-demo");

    $response->assertOk();
    $response->assertSee('Login as Instructor');
    $response->assertSee('Login as Student');
    $response->assertSee('Login as School Admin');
});

test('trying the demo as instructor generates access and logs the user in', function () {
    $this->seed(PricingTierSeeder::class);

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $response = $this->get("http://{$rootDomain}/try-demo/instructor");

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'instructor')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo as school admin generates access and logs the user in', function () {
    $this->seed(PricingTierSeeder::class);

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $response = $this->get("http://{$rootDomain}/try-demo/school-admin");

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'school-admin')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo as student generates access and logs the user in', function () {
    $this->seed(PricingTierSeeder::class);

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $response = $this->get("http://{$rootDomain}/try-demo/student");

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'student')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo reuses an existing valid access token instead of generating a new one', function () {
    $this->seed(PricingTierSeeder::class);

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $this->get("http://{$rootDomain}/try-demo/instructor");
    $firstAccess = DemoLmsAccess::where('school_id', $school->id)->where('role', 'instructor')->first();

    $this->get("http://{$rootDomain}/try-demo/instructor");
    $secondAccess = DemoLmsAccess::where('school_id', $school->id)->where('role', 'instructor')->first();

    expect($secondAccess->id)->toBe($firstAccess->id);
});

test('an invalid demo role is rejected', function () {
    $rootDomain = config('app.domain');

    $response = $this->get("http://{$rootDomain}/try-demo/superadmin");

    $response->assertNotFound();
});

test('admin login is only reachable on the admin subdomain', function () {
    $rootDomain = config('app.domain');
    $adminDomain = "admin.{$rootDomain}";

    $adminResponse = $this->get("http://{$adminDomain}/login");
    $adminResponse->assertOk();
    $adminResponse->assertViewIs('auth.admin-login');

    $rootResponse = $this->get("http://{$rootDomain}/login");
    $rootResponse->assertOk();
    $rootResponse->assertViewIs('auth.login');
});
