<?php

use App\Models\School;
use App\Models\User;
use App\Services\R2StorageService;

test('dashboard displays storage quota card', function () {
    $school = School::factory()->create();
    $user = User::factory()
        ->for($school)
        ->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee('Storage')
        ->assertSee('0 B');
});

test('storage quota displays correct limit and percentage', function () {
    $school = School::factory()->create();
    $user = User::factory()
        ->for($school)
        ->create();

    $service = new R2StorageService;
    $quota = $service->checkSchoolQuota($school->id);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee($quota['limit_gb'].' GB')
        ->assertSee(round($quota['percentage'], 1).'%');
});

test('storage card only shows for users with school', function () {
    $user = User::factory()->create([
        'school_id' => null,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertDontSee('0 B / 1 GB');
});
