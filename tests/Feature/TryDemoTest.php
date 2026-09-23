<?php

use App\Models\DemoLmsAccess;
use App\Models\School;

test('the try demo page is reachable', function () {
    $response = $this->get('/try-demo');

    $response->assertOk();
    $response->assertSee('Login as Teacher');
    $response->assertSee('Login as Student');
    $response->assertSee('Login as School Admin');
});

test('trying the demo as teacher generates access and logs the user in', function () {
    $school = School::where('name', 'School Demo')->first() ?? School::factory()->create(['name' => 'School Demo']);

    $response = $this->get('/try-demo/teacher');

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'teacher')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo as school admin generates access and logs the user in', function () {
    $school = School::where('name', 'School Demo')->first() ?? School::factory()->create(['name' => 'School Demo']);

    $response = $this->get('/try-demo/school-admin');

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'school-admin')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo as student generates access and logs the user in', function () {
    $school = School::where('name', 'School Demo')->first() ?? School::factory()->create(['name' => 'School Demo']);

    $response = $this->get('/try-demo/student');

    $access = DemoLmsAccess::where('school_id', $school->id)->where('role', 'student')->first();

    expect($access)->not->toBeNull();
    $response->assertRedirect($access->getLoginUrl());
});

test('trying the demo reuses an existing valid access token instead of generating a new one', function () {
    School::where('name', 'School Demo')->first() ?? School::factory()->create(['name' => 'School Demo']);

    $this->get('/try-demo/teacher');
    $firstAccess = DemoLmsAccess::where('role', 'teacher')->first();

    $this->get('/try-demo/teacher');
    $secondAccess = DemoLmsAccess::where('role', 'teacher')->first();

    expect($secondAccess->id)->toBe($firstAccess->id);
});

test('an invalid demo role is rejected', function () {
    School::where('name', 'School Demo')->first() ?? School::factory()->create(['name' => 'School Demo']);

    $response = $this->get('/try-demo/superadmin');

    $response->assertNotFound();
});

test('try demo login returns not found when no root demo school exists', function () {
    School::where('name', 'School Demo')->delete();

    $response = $this->get('/try-demo/teacher');

    $response->assertNotFound();
});
