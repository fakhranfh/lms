<?php

use App\Livewire\SchoolRegister;
use App\Models\School;
use Database\Seeders\PricingTierSeeder;
use Livewire\Livewire;

test('a school can register with a valid name and subdomain', function () {
    $this->seed(PricingTierSeeder::class);

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

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'custom')
        ->set('customDomain', 'lms.myschool.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(School::query()->where('domain', 'lms.myschool.com')->exists())->toBeTrue();
});

test('registration is rejected when the subdomain is already taken', function () {
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
    School::factory()->create(['domain' => 'lms.myschool.com']);

    Livewire::test(SchoolRegister::class)
        ->set('name', 'My School')
        ->set('domainType', 'custom')
        ->set('customDomain', 'lms.myschool.com')
        ->call('save')
        ->assertHasErrors('customDomain');
});

test('registration is rejected for an invalid custom domain', function (string $domain) {
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
