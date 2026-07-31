<?php

use App\Models\School;
use App\Models\User;
use App\Services\R2StorageService;

test('dashboard displays storage quota card for teacher', function () {
    $school = School::factory()->create();
    $user = User::factory()
        ->forSchool($school)
        ->create();
    $user->assignRole('Teacher');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee('Storage')
        ->assertSee('0 B');
});

test('storage quota displays correct limit and percentage', function () {
    $school = School::factory()->create();
    $user = User::factory()
        ->forSchool($school)
        ->create();
    $user->assignRole('Teacher');

    $service = app(R2StorageService::class);
    $quota = $service->checkSchoolQuota($school->id);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee($quota['limit_gb'].' GB')
        ->assertSee(round($quota['percentage'], 1).'%');
});

test('storage card does not show for students', function () {
    $school = School::factory()->create();
    $user = User::factory()
        ->forSchool($school)
        ->create();
    $user->assignRole('Student');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertDontSee('0 B / 1 GB');
});

test('storage card does not show for platform admin without a school', function () {
    $user = User::factory()->create([
        'school_id' => null,
    ]);
    $user->assignRole('Admin');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertDontSee('0 B / 1 GB');
});
