<?php

use Darejer\Components\Combobox;
use Darejer\Components\Display;
use Darejer\Components\SelectComponent;
use Darejer\Components\TextInput;
use Darejer\Tests\Fixtures\BadgeFixtureStatus;

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

it('omits prefillUrl by default', function () {
    $array = Combobox::make('country')
        ->options(['us' => 'United States'])
        ->toArray();

    expect($array)->not->toHaveKey('prefillUrl');
});

it('serializes prefillUrl when prefillFrom is set', function () {
    $array = Combobox::make('sales_order_id')
        ->options(['1' => 'SO-001'])
        ->prefillFrom('/darejer/sales/sales-invoices/prefill-from-order')
        ->toArray();

    expect($array)->toHaveKey('prefillUrl', '/darejer/sales/sales-invoices/prefill-from-order');
});

it('serializes a single label field as a string', function () {
    $array = Combobox::make('item_category_id')
        ->options(['1' => 'CAT-A'])
        ->toArray();

    expect($array)
        ->toHaveKey('labelField', 'name')
        ->not->toHaveKey('labelFields')
        ->not->toHaveKey('searchFields')
        ->not->toHaveKey('labelSeparator');
});

it('serializes labelFields and defaults searchFields when label is an array', function () {
    // Reflection is used to bypass model() route resolution — model()
    // looks up `<resource>.create` route which doesn't exist in tests.
    $combobox = Combobox::make('item_category_id');
    $reflection = new ReflectionClass($combobox);
    $reflection->getProperty('labelField')->setValue($combobox, ['code', 'name']);

    $array = $combobox->toArray();

    expect($array)
        ->toHaveKey('labelField', 'code')           // first field for legacy fallback
        ->toHaveKey('labelFields', ['code', 'name'])
        ->toHaveKey('labelSeparator', ' — ')
        ->toHaveKey('searchFields', ['code', 'name']);
});

it('lets searchFields be overridden independently of labelFields', function () {
    $combobox = Combobox::make('contact_id');
    $reflection = new ReflectionClass($combobox);
    $reflection->getProperty('labelField')->setValue($combobox, ['code', 'name']);
    $combobox->searchFields(['code', 'name', 'email', 'phone']);

    $array = $combobox->toArray();

    expect($array)
        ->toHaveKey('labelFields', ['code', 'name'])
        ->toHaveKey('searchFields', ['code', 'name', 'email', 'phone']);
});

it('honors a custom labelSeparator', function () {
    $combobox = Combobox::make('item_category_id')->labelSeparator(' / ');
    $reflection = new ReflectionClass($combobox);
    $reflection->getProperty('labelField')->setValue($combobox, ['code', 'name']);

    expect($combobox->toArray())->toHaveKey('labelSeparator', ' / ');
});

it('serializes a plain Display component', function () {
    $array = Display::make('voucher_no')->label('No.')->toArray();

    expect($array)
        ->toHaveKey('type', 'Display')
        ->toHaveKey('name', 'voucher_no')
        ->toHaveKey('label', 'No.')
        ->toHaveKey('displayType', 'text');
});

it('serializes a Display badge with color map', function () {
    $array = Display::make('status')
        ->label('Status')
        ->badge(['posted' => 'success', 'draft' => 'neutral'])
        ->toArray();

    expect($array)
        ->toHaveKey('displayType', 'badge')
        ->toHaveKey('badgeMap', ['posted' => 'success', 'draft' => 'neutral'])
        ->not->toHaveKey('badgeLabels');
});

it('serializes a Display badge with translated labels from an enum class', function () {
    $array = Display::make('status')
        ->label('Status')
        ->badge(BadgeFixtureStatus::class)
        ->toArray();

    expect($array)
        ->toHaveKey('displayType', 'badge')
        ->toHaveKey('badgeMap', ['posted' => 'success', 'draft' => 'neutral'])
        ->toHaveKey('badgeLabels', ['posted' => 'Posted', 'draft' => 'Draft']);
});

it('serializes a Display date with default format', function () {
    $array = Display::make('voucher_date')->date()->toArray();

    expect($array)
        ->toHaveKey('displayType', 'date')
        ->toHaveKey('dateFormat', 'Y-m-d');
});

it('serializes a Display money component with decimals and currency field', function () {
    $array = Display::make('grand_total')
        ->money(2, 'currency.code')
        ->toArray();

    expect($array)
        ->toHaveKey('displayType', 'money')
        ->toHaveKey('decimals', 2)
        ->toHaveKey('currencyField', 'currency.code');
});

it('serializes a Display boolean with default labels', function () {
    $array = Display::make('is_active')->boolean()->toArray();

    expect($array)
        ->toHaveKey('displayType', 'boolean')
        ->toHaveKey('booleanTrueLabel', 'Yes')
        ->toHaveKey('booleanFalseLabel', 'No');
});
