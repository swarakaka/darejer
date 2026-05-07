<?php

use Darejer\Components\Combobox;
use Darejer\Components\Display;
use Darejer\Components\Money;
use Darejer\Components\SelectComponent;
use Darejer\Components\TextInput;
use Darejer\Components\TreeGrid;
use Darejer\Tests\Fixtures\BadgeFixtureStatus;
use Darejer\Tests\TmpCurrency;
use Darejer\Tests\TmpCurrency2;
use Darejer\TreeGrid\TreeColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

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
        ->not->toHaveKey('searchFields');
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

it('serializes a Money component with default decimals and allowNegative', function () {
    $array = Money::make('amount')->label('Amount')->toArray();

    expect($array)
        ->toHaveKey('type', 'Money')
        ->toHaveKey('name', 'amount')
        ->toHaveKey('label', 'Amount')
        ->toHaveKey('decimals', 2)
        ->toHaveKey('allowNegative', true);
});

it('serializes a Money component with explicit decimals, currency and bounds', function () {
    $array = Money::make('grand_total')
        ->decimals(3)
        ->currency('USD')
        ->min(0)
        ->max(1_000_000)
        ->step(0.01)
        ->allowNegative(false)
        ->toArray();

    expect($array)
        ->toHaveKey('decimals', 3)
        ->toHaveKey('currency', 'USD')
        ->toHaveKey('min', 0)
        ->toHaveKey('max', 1_000_000)
        ->toHaveKey('step', 0.01)
        ->toHaveKey('allowNegative', false);
});

it('serializes a Money component with currencyField for record-resolved currency', function () {
    $array = Money::make('amount')
        ->currencyField('currency.code')
        ->toArray();

    expect($array)
        ->toHaveKey('currencyField', 'currency.code')
        ->not->toHaveKey('currencyDataUrl');
});

it('omits currency picker props when currencyPicker is not configured', function () {
    $array = Money::make('amount')->toArray();

    expect($array)
        ->not->toHaveKey('currencyDataUrl')
        ->not->toHaveKey('currencyValueField')
        ->not->toHaveKey('currencyKeyField')
        ->not->toHaveKey('currencyLabelField')
        ->not->toHaveKey('currencyDecimalsField')
        ->not->toHaveKey('currencySymbolField');
});

it('serializes Money currency picker props when configured against a model', function () {
    Route::get('/darejer/data/{model}', fn () => null)
        ->name('darejer.data.index');

    $modelClass = new class extends Model {};
    $modelAlias = class_alias($modelClass::class, 'Darejer\\Tests\\TmpCurrency');

    $array = Money::make('amount')
        ->decimals(2)
        ->currencyPicker(TmpCurrency::class)
        ->toArray();

    expect($array)
        ->toHaveKey('currencyDataUrl')
        ->toHaveKey('currencyValueField', 'currency_id')
        ->toHaveKey('currencyKeyField', 'id')
        ->toHaveKey('currencyLabelField', 'code')
        ->toHaveKey('currencyDecimalsField', 'minor_units');

    expect($array['currencyDataUrl'])->toContain('/darejer/data/');
});

it('lets currencyPicker override valueField, labelField and decimalsField', function () {
    Route::get('/darejer/data/{model}', fn () => null)
        ->name('darejer.data.index');

    $modelClass = new class extends Model {};
    if (! class_exists('Darejer\\Tests\\TmpCurrency2')) {
        class_alias($modelClass::class, 'Darejer\\Tests\\TmpCurrency2');
    }

    $array = Money::make('amount')
        ->currencyPicker(
            TmpCurrency2::class,
            valueField: 'fx_currency_id',
            labelField: 'iso_code',
            decimalsField: 'fraction_digits',
            symbolField: 'sign',
        )
        ->toArray();

    expect($array)
        ->toHaveKey('currencyValueField', 'fx_currency_id')
        ->toHaveKey('currencyLabelField', 'iso_code')
        ->toHaveKey('currencyDecimalsField', 'fraction_digits')
        ->toHaveKey('currencySymbolField', 'sign');
});

it('serializes a TreeGrid as fullWidth by default so it is not boxed into the 2-col Screen grid', function () {
    $array = TreeGrid::make('coa_tree')->toArray();

    expect($array)
        ->toHaveKey('type', 'TreeGrid')
        ->toHaveKey('fullWidth', true);
});

it('serializes a TreeColumn badge with a color map array', function () {
    $array = TreeColumn::make('status')
        ->badge(['posted' => 'success', 'draft' => 'neutral'])
        ->toArray();

    expect($array)
        ->toHaveKey('badge', json_encode(['posted' => 'success', 'draft' => 'neutral']))
        ->not->toHaveKey('badgeLabels');
});

it('serializes a TreeColumn badge with translated labels from an enum class', function () {
    $array = TreeColumn::make('status')
        ->badge(BadgeFixtureStatus::class)
        ->toArray();

    expect($array)
        ->toHaveKey('badge', json_encode(['posted' => 'success', 'draft' => 'neutral']))
        ->toHaveKey('badgeLabels', json_encode(['posted' => 'Posted', 'draft' => 'Draft']));
});
