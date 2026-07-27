<?php

use App\Enums\RoleName;
use App\Models\School;
use App\Models\User;
use Database\Seeders\DefaultRoleSeeder;
use Database\Seeders\PricingTierSeeder;

test('school admin can view payment page', function () {
    $this->seed([PricingTierSeeder::class, DefaultRoleSeeder::class]);
    $user = User::factory()->create(['school_id' => null]);
    $school = School::factory()->create();

    $user->schools()->attach($school->id);
    $schoolAdminRole = $school->roles()->where('name', RoleName::SchoolAdmin->value)->first();
    $user->assignRole($schoolAdminRole);

    $this->actingAs($user);
    $response = $this->get(route('school.payment.index', $school));

    $response->assertOk();
    $response->assertViewHas('school', $school);
});

test('non-admin user cannot view payment page', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $school = School::factory()->create();

    $this->actingAs($user);
    $response = $this->get(route('school.payment.index', $school));

    $response->assertStatus(403);
});

test('unauthenticated user cannot view payment page', function () {
    $this->seed(PricingTierSeeder::class);
    $school = School::factory()->create();

    $response = $this->get(route('school.payment.index', $school));

    $response->assertRedirect(route('login'));
});

test('payment page displays price breakdown for paid tier', function () {
    $this->seed([PricingTierSeeder::class, DefaultRoleSeeder::class]);
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = \App\Models\PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $school = School::factory()->create(['tier_id' => $plusTier->id]);

    $user->schools()->attach($school->id);
    $schoolAdminRole = $school->roles()->where('name', RoleName::SchoolAdmin->value)->first();
    $user->assignRole($schoolAdminRole);

    $this->actingAs($user);
    $response = $this->get(route('school.payment.index', $school));

    $response->assertOk();
    $response->assertSeeText($school->name);
    $response->assertSeeText($plusTier->name);
    $response->assertSeeText('Rp');
});

test('payment page displays free for free tier', function () {
    $this->seed([PricingTierSeeder::class, DefaultRoleSeeder::class]);
    $user = User::factory()->create(['school_id' => null]);
    $basicTier = \App\Models\PricingTier::query()->where('slug', 'basic')->firstOrFail();
    $school = School::factory()->create(['tier_id' => $basicTier->id]);

    $user->schools()->attach($school->id);
    $schoolAdminRole = $school->roles()->where('name', RoleName::SchoolAdmin->value)->first();
    $user->assignRole($schoolAdminRole);

    $this->actingAs($user);
    $response = $this->get(route('school.payment.index', $school));

    $response->assertOk();
    $response->assertSeeText('Free');
});
