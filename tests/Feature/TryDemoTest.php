<?php

use App\Models\DemoLmsAccess;
use App\Models\School;

test('the try demo page is reachable on the root domain', function () {
    $rootDomain = config('app.domain');

    $response = $this->get("http://{$rootDomain}/try-demo");

    $response->assertOk();
    $response->assertSee('Login as Teacher');
    $response->assertSee('Login as Student');
    $response->assertSee('Login as School Admin');
});

test('trying the demo as teacher generates access and logs the user in', function () {

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $response = $this->get("http://{$rootDomain}/try-demo/teacher");

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'teacher')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo as school admin generates access and logs the user in', function () {

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $response = $this->get("http://{$rootDomain}/try-demo/school-admin");

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'school-admin')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo as student generates access and logs the user in', function () {

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $response = $this->get("http://{$rootDomain}/try-demo/student");

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'student')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo reuses an existing valid access token instead of generating a new one', function () {

    $rootDomain = config('app.domain');
    $demoDomain = "school.{$rootDomain}";
    $school = School::where('domain', $demoDomain)->first() ?? School::factory()->create(['domain' => $demoDomain]);

    $this->get("http://{$rootDomain}/try-demo/teacher");
    $firstAccess = DemoLmsAccess::where('school_id', $school->id)->where('role', 'teacher')->first();

    $this->get("http://{$rootDomain}/try-demo/teacher");
    $secondAccess = DemoLmsAccess::where('school_id', $school->id)->where('role', 'teacher')->first();

    expect($secondAccess->id)->toBe($firstAccess->id);
});

test('an invalid demo role is rejected', function () {
    $rootDomain = config('app.domain');

    $response = $this->get("http://{$rootDomain}/try-demo/superadmin");

    $response->assertNotFound();
});
