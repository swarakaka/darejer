<?php

use Darejer\DataGrid\Column;

it('supports displayUsing callbacks', function () {
    $column = Column::make('company_id')
        ->displayUsing(fn (object $record) => $record->company_code ?? null);

    $displayUsing = $column->getDisplayUsing();

    expect($displayUsing)->not->toBeNull();
    expect($displayUsing((object) ['company_code' => 'HQ']))->toBe('HQ');
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
