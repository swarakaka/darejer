<?php

use Darejer\DataGrid\Column;

it('supports displayUsing callbacks', function () {
    $column = Column::make('company_id')
        ->displayUsing(fn (object $record) => $record->company_code ?? null);

    $displayUsing = $column->getDisplayUsing();

    expect($displayUsing)->not->toBeNull();
    expect($displayUsing((object) ['company_code' => 'HQ']))->toBe('HQ');
});
