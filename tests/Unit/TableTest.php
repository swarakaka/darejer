<?php

declare(strict_types=1);

use Darejer\Components\Table;
use Darejer\Table\Column;

it('serializes a Table component with its columns', function (): void {
    $array = Table::make('lines')
        ->label('Items')
        ->columns([
            Column::make('item.name')->label('Item'),
            Column::make('qty')->number(2)->width('6rem'),
            Column::make('rate')->money(2, 'currency.code', 'currency.minor_units')->width('8rem'),
        ])
        ->toArray();

    expect($array['type'])->toBe('Table')
        ->and($array['name'])->toBe('lines')
        ->and($array['label'])->toBe('Items')
        ->and($array['tableColumns'])->toHaveCount(3);

    [$item, $qty, $rate] = $array['tableColumns'];

    expect($item['field'])->toBe('item.name')
        ->and($item['type'])->toBe('text');

    expect($qty['field'])->toBe('qty')
        ->and($qty['type'])->toBe('number')
        ->and($qty['decimals'])->toBe(2)
        ->and($qty['alignRight'])->toBeTrue();

    expect($rate['type'])->toBe('money')
        ->and($rate['decimals'])->toBe(2)
        ->and($rate['currencyField'])->toBe('currency.code')
        ->and($rate['decimalsField'])->toBe('currency.minor_units');
});

it('omits null props from the column array', function (): void {
    $col = Column::make('notes')->label('Notes')->toArray();

    expect($col)->toHaveKey('field', 'notes')
        ->and($col)->toHaveKey('label', 'Notes')
        ->and($col)->toHaveKey('type', 'text')
        ->and($col)->not->toHaveKey('decimals')
        ->and($col)->not->toHaveKey('badgeMap')
        ->and($col)->not->toHaveKey('alignRight');
});

it('supports badge columns from an array color map', function (): void {
    $col = Column::make('status')
        ->badge(['posted' => 'success', 'draft' => 'neutral'])
        ->toArray();

    expect($col['type'])->toBe('badge')
        ->and($col['badgeMap'])->toBe(['posted' => 'success', 'draft' => 'neutral']);
});

it('flags translatable columns in serialized output', function (): void {
    $col = Column::make('description')->translatable()->toArray();

    expect($col['translatable'])->toBeTrue();
});

it('omits translatable when not set', function (): void {
    $col = Column::make('qty')->number(2)->toArray();

    expect($col)->not->toHaveKey('translatable');
});
