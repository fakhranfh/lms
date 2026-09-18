<?php

use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\Validator;

dataset('invalid passwords', [
    'too short' => ['Ab1!'],
    'missing letter' => ['12345678!'],
    'missing number' => ['Abcdefgh!'],
    'missing symbol' => ['Abcdefgh1'],
]);

test('rejects passwords that do not satisfy the complexity rule', function (string $password) {
    $validator = Validator::make(
        ['password' => $password, 'password_confirmation' => $password],
        ['password' => PasswordPolicy::rules()],
    );

    expect($validator->fails())->toBeTrue();
})->with('invalid passwords');

test('accepts a password with the minimum length, a letter, a number, and a symbol', function () {
    $validator = Validator::make(
        ['password' => 'Abcdefg1!', 'password_confirmation' => 'Abcdefg1!'],
        ['password' => PasswordPolicy::rules()],
    );

    expect($validator->fails())->toBeFalse();
});

test('nullable rules skip the complexity check for an empty value', function () {
    $validator = Validator::make(
        ['password' => ''],
        ['password' => PasswordPolicy::rules(required: false, confirmed: false)],
    );

    expect($validator->fails())->toBeFalse();
});
