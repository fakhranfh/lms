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

test('a slug is generated from the school name on creation', function () {
    $school = School::factory()->create(['name' => 'Contoh Sekolah']);

    expect($school->slug)->toBe('contoh-sekolah');
});

test('a duplicate slug is disambiguated with a numeric suffix', function () {
    $first = School::factory()->create(['name' => 'Contoh Sekolah']);
    $second = School::factory()->create(['name' => 'Contoh Sekolah']);

    expect($first->slug)->toBe('contoh-sekolah')
        ->and($second->slug)->toBe('contoh-sekolah-2');
});

test('a school slug cannot be changed after creation', function () {
    $school = School::factory()->create(['name' => 'Contoh Sekolah']);

    $school->forceFill(['slug' => 'something-else'])->save();

    expect($school->fresh()->slug)->toBe('contoh-sekolah');
});
