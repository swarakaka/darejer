<?php

declare(strict_types=1);

use Darejer\DataGrid\Column;
use Darejer\DataTable\DataTable;
use Illuminate\Database\Eloquent\Model;

class DataTableTestModel extends Model
{
    //
}

function callBuildRenderColumns(DataTable $table): array
{
    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('buildRenderColumns');
    $method->setAccessible(true);

    return $method->invoke($table);
}

it('hides the id column by default but keeps it in the column list', function () {
    $table = DataTable::make(DataTableTestModel::class)
        ->columns([
            Column::make('id')->label('#'),
            Column::make('code')->label('Code'),
        ]);

    $columns = callBuildRenderColumns($table);
    $serialized = array_map(fn (Column $c) => $c->toArray(), $columns);

    expect($serialized)->toHaveCount(2);
    expect($serialized[0]['field'])->toBe('id');
    expect($serialized[0]['hidden'] ?? false)->toBeTrue();
    expect($serialized[1]['field'])->toBe('code');
    expect($serialized[1]['hidden'] ?? false)->toBeFalse();
});

it('does not add a numeric column unless numeric() is called', function () {
    $table = DataTable::make(DataTableTestModel::class)
        ->columns([Column::make('code')->label('Code')]);

    $fields = array_map(fn (Column $c) => $c->getField(), callBuildRenderColumns($table));

    expect($fields)->toBe(['code']);
});

it('prepends a # row-number column when numeric() is enabled', function () {
    $table = DataTable::make(DataTableTestModel::class)
        ->columns([Column::make('code')->label('Code')])
        ->numeric();

    $columns = callBuildRenderColumns($table);
    $serialized = array_map(fn (Column $c) => $c->toArray(), $columns);

    expect($columns)->toHaveCount(2);
    expect($columns[0]->getField())->toBe('__row_number');
    expect($serialized[0]['label'])->toBe('#');
    expect($serialized[0]['align'] ?? null)->toBe('right');
    expect($columns[1]->getField())->toBe('code');
});

it('numeric() returns the same DataTable instance for chaining', function () {
    $table = DataTable::make(DataTableTestModel::class);

    expect($table->numeric())->toBe($table);
    expect($table->numeric(false))->toBe($table);
});
