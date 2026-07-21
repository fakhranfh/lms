<?php

use App\Models\School;
use Database\Seeders\PricingTierSeeder;

test('a school can register with a valid name and domain', function () {
    $this->seed(PricingTierSeeder::class);

    $rootDomain = config('app.domain');
    $schoolDomain = "myschool.{$rootDomain}";

    $response = $this->post("http://{$rootDomain}/register-school", [
        'name' => 'My School',
        'domain' => $schoolDomain,
    ]);

    $response->assertRedirect("http://{$schoolDomain}/register");

    expect(School::query()->where('domain', $schoolDomain)->exists())->toBeTrue();
});

test('registration is rejected when the domain is already taken', function () {
    $rootDomain = config('app.domain');
    $schoolDomain = "myschool.{$rootDomain}";

    School::factory()->create(['domain' => $schoolDomain]);

    $response = $this->post("http://{$rootDomain}/register-school", [
        'name' => 'My School',
        'domain' => $schoolDomain,
    ]);

    $response->assertSessionHasErrors('domain');
});

test('registration is rejected for an invalid hostname-like domain', function (string $domain) {
    $rootDomain = config('app.domain');

    $response = $this->post("http://{$rootDomain}/register-school", [
        'name' => 'My School',
        'domain' => $domain,
    ]);

    $response->assertSessionHasErrors('domain');
})->with([
    'has spaces' => 'my school.example.com',
    'has protocol' => 'http://myschool.example.com',
    'has path' => 'myschool.example.com/path',
    'has trailing slash' => 'myschool.example.com/',
]);
