<?php

use App\Enums\RoleName;
use App\Livewire\SchoolRegister;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use Database\Seeders\PricingTierSeeder;
use Livewire\Livewire;

test('a school can register with a valid name and subdomain', function () {
    $this->seed(PricingTierSeeder::class);
    $this->actingAs(User::factory()->create(['school_id' => null]));

    $rootDomain = config('app.domain');
    $schoolDomain = "myschool.{$rootDomain}";

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->call('save')
        ->assertHasNoErrors();

    expect(School::query()->where('domain', $schoolDomain)->exists())->toBeTrue();
});

test('a school can register with a custom domain', function () {
    $this->seed(PricingTierSeeder::class);
    $this->actingAs(User::factory()->create(['school_id' => null]));

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'custom')
        ->set('customDomain', 'lms.myschool.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(School::query()->where('domain', 'lms.myschool.com')->exists())->toBeTrue();
});

test('registering a school attaches the current user as school admin', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $this->actingAs($user);

    $schoolDomain = 'myschool.'.config('app.domain');

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->call('save')
        ->assertHasNoErrors();

    $school = School::query()->where('domain', $schoolDomain)->firstOrFail();

    expect($school->admins->contains($user->id))->toBeTrue()
        ->and($user->hasRole(RoleName::SchoolAdmin))->toBeTrue();
});

test('a school registers with the basic tier by default', function () {
    $this->seed(PricingTierSeeder::class);
    $this->actingAs(User::factory()->create(['school_id' => null]));

    $schoolDomain = 'myschool.'.config('app.domain');

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->call('save')
        ->assertHasNoErrors();

    $school = School::query()->where('domain', $schoolDomain)->firstOrFail();

    expect($school->tier->slug)->toBe('basic');
});

test('a school registers with the tier selected from the pricing page', function () {
    $this->seed(PricingTierSeeder::class);
    $this->actingAs(User::factory()->create(['school_id' => null]));

    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $schoolDomain = 'myschool.'.config('app.domain');

    Livewire::withQueryParams(['tier' => $plusTier->id])
        ->test(SchoolRegister::class)
        ->assertSet('tierId', (string) $plusTier->id)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->call('save')
        ->assertHasNoErrors();

    $school = School::query()->where('domain', $schoolDomain)->firstOrFail();

    expect($school->tier->id)->toBe($plusTier->id);
});

test('registration is rejected for an unknown tier', function () {
    $this->seed(PricingTierSeeder::class);
    $this->actingAs(User::factory()->create(['school_id' => null]));

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->set('tierId', 'not-a-real-tier-id')
        ->call('save')
        ->assertHasErrors('tierId');
});

test('guests are redirected to the get-started account step', function () {
    Livewire::test(SchoolRegister::class)
        ->assertRedirect(route('get-started'));
});

test('registration is rejected when the subdomain is already taken', function () {
    $this->actingAs(User::factory()->create(['school_id' => null]));

    $rootDomain = config('app.domain');
    $schoolDomain = "myschool.{$rootDomain}";

    School::factory()->create(['domain' => $schoolDomain]);

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->call('save')
        ->assertHasErrors('subdomain');
});

test('registration is rejected when the custom domain is already taken', function () {
    $this->actingAs(User::factory()->create(['school_id' => null]));

    School::factory()->create(['domain' => 'lms.myschool.com']);

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'custom')
        ->set('customDomain', 'lms.myschool.com')
        ->call('save')
        ->assertHasErrors('customDomain');
});

test('registration is rejected for an invalid custom domain', function (string $domain) {
    $this->actingAs(User::factory()->create(['school_id' => null]));

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'custom')
        ->set('customDomain', $domain)
        ->call('save')
        ->assertHasErrors('customDomain');
})->with([
    'has spaces' => 'my school.example.com',
    'has protocol' => 'http://myschool.example.com',
    'has path' => 'myschool.example.com/path',
    'has trailing slash' => 'myschool.example.com/',
]);

test('registration is rejected for an invalid subdomain label', function (string $subdomain) {
    $this->actingAs(User::factory()->create(['school_id' => null]));

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', $subdomain)
        ->call('save')
        ->assertHasErrors('subdomain');
})->with([
    'has spaces' => 'my school',
    'has dot' => 'my.school',
    'has slash' => 'my/school',
]);

test('registering with a free tier redirects to dashboard', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $this->actingAs($user);

    $basicTier = PricingTier::query()->where('slug', 'basic')->firstOrFail();

    Livewire::withQueryParams(['tier' => $basicTier->id])
        ->test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->call('save')
        ->assertRedirect(route('manage.schools.index'));
});

test('registering with a paid tier creates school and redirects to payment', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $this->actingAs($user);

    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $schoolDomain = 'myschool.'.config('app.domain');

    Livewire::withQueryParams(['tier' => $plusTier->id])
        ->test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'subdomain')
        ->set('subdomain', 'myschool')
        ->call('save')
        ->assertHasNoErrors();

    $school = School::query()->where('domain', $schoolDomain)->firstOrFail();
    expect($school->tier->id)->toBe($plusTier->id);
});
