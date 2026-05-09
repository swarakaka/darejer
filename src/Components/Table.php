<?php

namespace Darejer\Components;

use Darejer\Table\Column;

/**
 * Read-only tabular component for Show screens. Renders rows from a record
 * relation (`lines`, `items`, ...) without any input controls — pair it with
 * {@see \Darejer\Components\EditableTable} on the matching edit form.
 *
 *   Table::make('lines')
 *       ->label(__('Items'))
 *       ->columns([
 *           Column::make('item.name')->label(__('Item')),
 *           Column::make('qty')->number(2)->width('6rem'),
 *           Column::make('rate')->money(2)->width('8rem'),
 *       ])
 */
class Table extends BaseComponent
{
    /** @var array<int, array<string, mixed>> */
    protected array $tableColumns = [];

    protected ?string $emptyMessage = null;

    protected bool $fullWidth = true;

    /**
     * @param  array<int, Column|array<string, mixed>>  $columns
     */
    public function columns(array $columns): static
    {
        $this->tableColumns = array_map(
            fn (Column|array $col) => $col instanceof Column ? $col->toArray() : $col,
            $columns,
        );

        return $this;
    }

    public function emptyMessage(string $message): static
    {
        $this->emptyMessage = $message;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Table';
    }

    protected function componentProps(): array
    {
        return [
            'tableColumns' => $this->tableColumns,
            'emptyMessage' => $this->emptyMessage,
        ];
    }
}
