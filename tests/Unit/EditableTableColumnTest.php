<?php

declare(strict_types=1);

use Darejer\EditableTable\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class EditableTableColumnTestItem extends Model
{
    protected $table = 'editable_table_column_items';
}

beforeEach(function (): void {
    Route::get('/darejer/data/{model}', fn () => null)
        ->name('darejer.data.index');
});

it('emits filtersFrom mapping when configured', function (): void {
    $array = Column::make('item_id')
        ->combobox(EditableTableColumnTestItem::class, 'id', ['code', 'name'])
        ->filtersFrom(['warehouse_id' => 'from_warehouse_id'])
        ->toArray();

    expect($array['filtersFrom'])->toBe(['warehouse_id' => 'from_warehouse_id']);
});

it('omits filtersFrom when not configured', function (): void {
    $array = Column::make('item_id')
        ->combobox(EditableTableColumnTestItem::class, 'id', ['code', 'name'])
        ->toArray();

    expect($array)->not->toHaveKey('filtersFrom');
});
