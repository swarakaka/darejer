<?php

use Darejer\Rules\TranslatableRule;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    config()->set('darejer.languages', ['en', 'fr']);
    config()->set('darejer.default_language', 'en');
});

it('passes when default locale is filled and required', function () {
    $v = Validator::make(
        ['name' => ['en' => 'Hello', 'fr' => 'Bonjour']],
        ['name' => [new TranslatableRule(required: true, max: 255)]],
    );

    expect($v->fails())->toBeFalse();
});

it('attaches the required error to the default locale, not the parent', function () {
    $v = Validator::make(
        ['name' => ['fr' => 'Bonjour']],
        ['name' => [new TranslatableRule(required: true)]],
    );

    expect($v->fails())->toBeTrue();
    expect($v->errors()->has('name.en'))->toBeTrue();
    expect($v->errors()->has('name'))->toBeFalse();
});

it('attaches max-length errors to the offending locale only', function () {
    $v = Validator::make(
        ['name' => ['en' => 'OK', 'fr' => str_repeat('x', 256)]],
        ['name' => [new TranslatableRule(max: 255)]],
    );

    expect($v->fails())->toBeTrue();
    expect($v->errors()->has('name.fr'))->toBeTrue();
    expect($v->errors()->has('name.en'))->toBeFalse();
});

it('rejects unsupported locale keys on the parent', function () {
    $v = Validator::make(
        ['name' => ['en' => 'OK', 'xx' => 'Bad']],
        ['name' => [new TranslatableRule]],
    );

    expect($v->fails())->toBeTrue();
    expect($v->errors()->has('name'))->toBeTrue();
});

it('rejects non-array values on the parent', function () {
    $v = Validator::make(
        ['name' => 'plain string that is not json'],
        ['name' => [new TranslatableRule]],
    );

    expect($v->fails())->toBeTrue();
    expect($v->errors()->has('name'))->toBeTrue();
});

it('decodes JSON strings before validating', function () {
    $v = Validator::make(
        ['name' => json_encode(['en' => 'Hello'])],
        ['name' => [new TranslatableRule(required: true, max: 255)]],
    );

    expect($v->fails())->toBeFalse();
});

it('treats null translations as nullable per locale', function () {
    $v = Validator::make(
        ['description' => ['en' => 'present', 'fr' => null]],
        ['description' => ['nullable', new TranslatableRule(max: 100)]],
    );

    expect($v->fails())->toBeFalse();
});

it('skips validation entirely when nullable parent is null', function () {
    $v = Validator::make(
        ['description' => null],
        ['description' => ['nullable', new TranslatableRule(required: true, max: 100)]],
    );

    expect($v->fails())->toBeFalse();
});
