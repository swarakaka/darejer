<?php

use Darejer\Components\Combobox;
use Darejer\Components\SelectComponent;
use Darejer\Components\TextInput;

it('serializes a TextInput component', function () {
    $component = TextInput::make('email')
        ->label('Email')
        ->email()
        ->required()
        ->placeholder('you@example.com');

    $array = $component->toArray();

    expect($array)
        ->toHaveKey('type', 'TextInput')
        ->toHaveKey('name', 'email')
        ->toHaveKey('label', 'Email')
        ->toHaveKey('required', true)
        ->toHaveKey('inputType', 'email')
        ->toHaveKey('placeholder', 'you@example.com');
});

it('serializes a SelectComponent with options', function () {
    $component = SelectComponent::make('status')
        ->label('Status')
        ->options(['active' => 'Active', 'draft' => 'Draft']);

    $array = $component->toArray();

    expect($array['options'])->toHaveCount(2);
    expect($array['options'][0])->toMatchArray(['value' => 'active', 'label' => 'Active']);
});

it('returns null for components hidden by canSee', function () {
    $component = TextInput::make('secret')
        ->canSee('nonexistent.permission');

    expect($component->toArray())->toBeNull();
});

it('serializes dependOn with eq operator', function () {
    $component = TextInput::make('reason')
        ->label('Reason')
        ->dependOn('status', 'archived');

    $array = $component->toArray();

    expect($array['dependOn'])->toMatchArray([
        'field' => 'status',
        'operator' => 'eq',
        'value' => 'archived',
    ]);
});

it('serializes dependOnIn with in operator', function () {
    $component = TextInput::make('duration')
        ->dependOnIn('type', ['service']);

    expect($component->toArray()['dependOn'])->toMatchArray([
        'field' => 'type',
        'operator' => 'in',
        'value' => ['service'],
    ]);
});

it('serializes dependOnNotEmpty', function () {
    $component = TextInput::make('discount')
        ->dependOnNotEmpty('price');

    expect($component->toArray()['dependOn'])->toMatchArray([
        'field' => 'price',
        'operator' => 'notEmpty',
    ]);
});

it('serializes a Combobox as clearable by default', function () {
    $array = Combobox::make('country')
        ->options(['us' => 'United States', 'fr' => 'France'])
        ->toArray();

    expect($array)
        ->toHaveKey('type', 'Combobox')
        ->toHaveKey('clearable', true);
});

it('lets clearable be turned off', function () {
    $array = Combobox::make('country')
        ->options(['us' => 'United States'])
        ->clearable(false)
        ->toArray();

    expect($array)->toHaveKey('clearable', false);
});
