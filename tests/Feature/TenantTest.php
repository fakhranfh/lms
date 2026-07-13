<?php

use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

test('a tenant can be created with a valid uuid', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->id)->toBeString()
        ->and(Str::isUuid($tenant->id))->toBeTrue();
});

test('a tenant domain must be unique', function () {
    Tenant::factory()->create(['domain' => 'example.com']);

    expect(fn () => Tenant::factory()->create(['domain' => 'example.com']))
        ->toThrow(QueryException::class);
});
