<?php

use Darejer\DataGrid\Column;

it('supports displayUsing callbacks', function () {
    $column = Column::make('company_id')
        ->displayUsing(fn (object $record) => $record->company_code ?? null);

    $displayUsing = $column->getDisplayUsing();

    expect($displayUsing)->not->toBeNull();
    expect($displayUsing((object) ['company_code' => 'HQ']))->toBe('HQ');
});

it('supports format callbacks receiving value and model row', function () {
    $column = Column::make('item_category_id')
        ->format(fn ($value, $row) => $row->category?->code ?? '—');

    $format = $column->getFormat();

    $row = (object) [
        'item_category_id' => 7,
        'category' => (object) ['code' => 'CAT-A'],
    ];

    expect($format)->not->toBeNull();
    expect($format(7, $row))->toBe('CAT-A');
    expect($format(null, (object) ['item_category_id' => null, 'category' => null]))->toBe('—');
});

it('supports boolean display type with default labels', function () {
    $column = Column::make('is_paid')->boolean();

    expect($column->getDisplayType())->toBe('boolean');
    expect($column->getBooleanTrueLabel())->toBe(__('Yes'));
    expect($column->getBooleanFalseLabel())->toBe(__('No'));
});

it('supports boolean display type with custom labels', function () {
    $column = Column::make('is_active')->boolean('Active', 'Inactive');

    expect($column->getDisplayType())->toBe('boolean');
    expect($column->getBooleanTrueLabel())->toBe('Active');
    expect($column->getBooleanFalseLabel())->toBe('Inactive');
});

it('supports number display type with default decimals', function () {
    $column = Column::make('qty')->number();

    expect($column->getDisplayType())->toBe('number');
    expect($column->getDecimals())->toBe(0);
});

it('supports number display type with custom decimals', function () {
    $column = Column::make('rate')->number(3);

    expect($column->getDisplayType())->toBe('number');
    expect($column->getDecimals())->toBe(3);
});

it('supports money display type with default decimals', function () {
    $column = Column::make('amount')->money();

    expect($column->getDisplayType())->toBe('money');
    expect($column->getDecimals())->toBe(2);
    expect($column->getCurrencyField())->toBeNull();
});

it('supports money display type with decimals and currency field', function () {
    $column = Column::make('amount')->money(4, 'currency.code');

    expect($column->getDisplayType())->toBe('money');
    expect($column->getDecimals())->toBe(4);
    expect($column->getCurrencyField())->toBe('currency.code');
});
