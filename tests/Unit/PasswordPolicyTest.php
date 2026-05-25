<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

it('rejects passwords shorter than 8 characters in non-production environments', function () {
    $validator = Validator::make(
        ['password' => 'short7!'],
        ['password' => ['required', Password::defaults()]],
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('password'))->toContain('at least 8');
});

it('accepts an 8 character password in non-production environments', function () {
    $validator = Validator::make(
        ['password' => 'longenough'],
        ['password' => ['required', Password::defaults()]],
    );

    expect($validator->fails())->toBeFalse();
});
