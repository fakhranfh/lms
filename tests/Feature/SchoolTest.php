<?php

use App\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

test('a school can be created with a valid uuid', function () {
    $school = School::factory()->create();

    expect($school->id)->toBeString()
        ->and(Str::isUuid($school->id))->toBeTrue();
});

test('a school domain must be unique', function () {
    School::factory()->create(['domain' => 'example.com']);

    expect(fn () => School::factory()->create(['domain' => 'example.com']))
        ->toThrow(QueryException::class);
});
