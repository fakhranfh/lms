<?php

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

beforeEach(function () {
    Schema::create('has_uuid_test_models', function ($table) {
        $table->uuid('id')->primary();
        $table->string('name')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('has_uuid_test_models');
});

test('a model using HasUuid receives a valid UUIDv4 id on creation', function () {
    $model = new class extends Model
    {
        use HasUuid;

        protected $table = 'has_uuid_test_models';

        protected $fillable = ['name'];
    };

    $created = $model::create(['name' => 'example']);

    expect($created->incrementing)->toBeFalse()
        ->and($created->getKeyType())->toBe('string')
        ->and($created->id)->not->toBeNull()
        ->and(Str::isUuid($created->id))->toBeTrue()
        ->and(Uuid::fromString($created->id)->getVersion())->toBe(4);
});
